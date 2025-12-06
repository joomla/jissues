<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\CategoryModel;
use App\Tracker\Model\IssueModel;
use App\Tracker\Table\ActivitiesTable;
use Joomla\Date\Date;
use Joomla\Http\Exception\UnexpectedResponseException;
use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Github\GithubFactory;
use JTracker\Helper\GitHubHelper;

/**
 * Controller class to save an item via the Tracker App.
 *
 * @since  1.0
 */
class Save extends AbstractTrackerController
{
    /**
     * Execute the controller.
     *
     * @return  string  The rendered view.
     *
     * @since   1.0
     * @throws  \JTracker\Authentication\Exception\AuthenticationException
     * @throws  \RuntimeException
     * @throws  \UnexpectedValueException
     */
    public function execute()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $src = $application->input->get('item', [], 'array');

        $application->getLogger()->debug('Submitted data from save controller.', ['data' => $src]);

        $user    = $application->getUser();
        $project = $application->getProject();

        $model = new IssueModel($this->getContainer()->get('db'));
        $model->setProject($project);

        $issueNumber = isset($src['issue_number']) ? (int) $src['issue_number'] : 0;

        if (!$issueNumber) {
            $application->getLogger()->error('No issue number received from save controller.', ['data' => $src]);

            throw new \UnexpectedValueException('No issue number received.');
        }

        $item = $model->getItem($issueNumber);

        $data = [];

        if ($user->check('edit')) {
            // The user has full "edit" permission.
            $data = $src;

            if (!empty($item->labels)) {
                $data['labels'] = explode(',', $item->labels);
            }

            $data['milestone_id'] = $item->milestone_id;
        } elseif ($user->canEditOwn($item->opened_by)) {
            // The user has "edit own" permission.
            $data['id']              = (int) $src['id'];
            $data['issue_number']    = (int) $src['issue_number'];
            $data['title']           = $src['title'];
            $data['description_raw'] = $src['description_raw'];

            // Take the remaining values from the stored item
            if (!empty($item->labels)) {
                $data['labels'] = explode(',', $item->labels);
            }

            $data['status']       = $item->status;
            $data['priority']     = $item->priority;
            $data['build']        = $item->build;
            $data['rel_number']   = $item->rel_number;
            $data['rel_type']     = $item->rel_type;
            $data['easy']         = $item->easy;
            $data['milestone_id'] = $item->milestone_id;
        } else {
            // The user has no "edit" permission.
            throw new AuthenticationException($user, 'edit');
        }

        $gitHub = GithubFactory::getInstance($application);

        // Check if the state has changed (e.g. open/closed)
        $oldState = $model->getOpenClosed($item->status);
        $state    = $model->getOpenClosed($data['status']);

        // Project is managed on GitHub
        if ($project->gh_user && $project->gh_project) {
            // @todo assignee
            $assignee = null;

            // Prepare labels
            $ghLabels = [];

            if (!empty($data['labels'])) {
                foreach ($project->getLabels() as $id => $label) {
                    if (\in_array($id, $data['labels'])) {
                        $ghLabels[] = $label->name;
                    }
                }
            }

            // Prepare milestone
            $ghMilestone = null;

            if (!empty($data['milestone_id'])) {
                foreach ($project->getMilestones() as $milestone) {
                    if ($milestone->milestone_id == $data['milestone_id']) {
                        $ghMilestone = $milestone->milestone_number;
                    }
                }
            }

            try {
                $gitHubResponse = $this->updateGitHub(
                    $item->issue_number,
                    $data,
                    $state,
                    $oldState,
                    $assignee,
                    $ghMilestone,
                    $ghLabels
                );

                // Set the modified_date from GitHub (important!)
                $data['modified_date'] = $gitHubResponse->updated_at;
            } catch (UnexpectedResponseException $exception) {
                $application->getLogger()->error(
                    \sprintf(
                        'Error code %1$s received from GitHub when editing an issue with the following data:'
                        . ' GitHub User: %2$s; GitHub Repo: %3$s; Issue Number: %4$s; State: %5$s, Old state: %6$s',
                        $exception->getCode(),
                        $project->gh_user,
                        $project->gh_project,
                        $item->issue_number,
                        $state,
                        $oldState
                    ),
                    ['exception' => $exception, 'response' => $exception->getResponse()->body]
                );

                throw new \RuntimeException('Invalid response from GitHub', $exception->getCode(), $exception);
            }

            // Render the description text using GitHub's markdown renderer.
            $data['description'] = $gitHub->markdown->render(
                $data['description_raw'],
                'gfm',
                $project->gh_user . '/' . $project->gh_project
            );
        } else {
            // Project is managed by JTracker only

            // Render the description text using GitHub's markdown renderer.
            $data['description'] = $gitHub->markdown->render($src['description_raw'], 'markdown');

            $data['modified_date'] = (new Date())->format($this->getContainer()->get('db')->getDateFormat());
        }

        try {
            $data['modified_by'] = $user->username;

            // If the user have edit permission, let him / her modify the categories.
            if ($user->check('edit')) {
                $category = [
                    'issue_id'     => $data['id'],
                    'modified_by'  => $user->username,
                    'categories'   => $application->input->get('categories', null, 'array'),
                    'issue_number' => $data['issue_number'],
                    'project_id'   => $project->project_id,
                ];

                (new CategoryModel($this->getContainer()->get('db')))->updateCategory($category);
            }

            // Pass the old and new states into the save method
            $data['old_state'] = $oldState;
            $data['new_state'] = $state;

            // Values that are not supposed to change.
            $data['commits']      = $item->commits;
            $data['pr_head_sha']  = $item->pr_head_sha;
            $data['pr_head_user'] = $item->pr_head_user;
            $data['pr_head_ref']  = $item->pr_head_ref;

            // Save the record.
            $model->save($data);

            $comment = $application->input->get('comment', '', 'raw');

            // Save the comment.
            if ($comment) {
                /** @var \JTracker\Github\Github $github */
                $github = $this->getContainer()->get('gitHub');

                $project = $application->getProject();

                $comment .= (new GitHubHelper($github))->getApplicationComment($application, $project, $issueNumber);

                $data = new \stdClass();
                $db   = $this->getContainer()->get('db');

                if ($project->gh_user && $project->gh_project) {
                    $gitHubResponse = $github->issues->comments->create(
                        $project->gh_user,
                        $project->gh_project,
                        $issueNumber,
                        $comment
                    );

                    if (!isset($gitHubResponse->id)) {
                        throw new \RuntimeException('Invalid response from GitHub');
                    }

                    $data->created_at = $gitHubResponse->created_at;
                    $data->opened_by  = $gitHubResponse->user->login;
                    $data->comment_id = $gitHubResponse->id;
                    $data->text_raw   = $gitHubResponse->body;

                    $data->text = $github->markdown->render(
                        $comment,
                        'gfm',
                        $project->gh_user . '/' . $project->gh_project
                    );
                } else {
                    $data->created_at = (new Date())->format($db->getDateFormat());
                    $data->opened_by  = $application->getUser()->username;
                    $data->comment_id = '???';

                    $data->text_raw = $comment;

                    $data->text = $github->markdown->render($comment, 'markdown');
                }

                $table = new ActivitiesTable($db);

                $table->event         = 'comment';
                $table->created_date  = $data->created_at;
                $table->project_id    = $project->project_id;
                $table->issue_number  = $issueNumber;
                $table->gh_comment_id = $data->comment_id;
                $table->user          = $data->opened_by;
                $table->text          = $data->text;
                $table->text_raw      = $data->text_raw;

                $table->store();
            }

            $application->enqueueMessage('The changes have been saved.', 'success')
                ->redirect('/tracker/' . $application->input->get('project_alias') . '/' . $issueNumber);
        } catch (\RuntimeException $exception) {
            $application->enqueueMessage($exception->getMessage(), 'error');

            // @todo preserve data when returning to edit view on failure.
            $application->redirect(
                $application->get('uri.base.path')
                . 'tracker/' . $application->input->get('project_alias') . '/' . $issueNumber . '/edit'
            );
        }

        return parent::execute();
    }

    /**
     * Update the issue on GitHub.
     *
     * The method will first try to perform the action with the logged in user credentials and then, if it fails, perform
     * the action using a configured "edit bot". If the GitHub status changes (e.g. open <=> close), a comment will be
     * created automatically stating that the action has been performed by a bot.
     *
     * @param   integer  $issueNumber  The issue number.
     * @param   array    $data         The issue data.
     * @param   string   $state        The issue state (either 'open' or 'closed).
     * @param   string   $oldState     The previous issue state.
     * @param   string   $assignee     The login for the GitHub user that this issue should be assigned to.
     * @param   integer  $milestone    The milestone to associate this issue with.
     * @param   array    $labels       The labels to associate with this issue.
     *
     * @return  object  The issue data
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    private function updateGitHub($issueNumber, array $data, $state, $oldState, $assignee, $milestone, $labels)
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $project = $application->getProject();

        try {
            // Try to perform the action on behalf of current user
            $gitHub = GithubFactory::getInstance($application);

            // Look if we have a bot user configured
            if ($project->getGh_Editbot_User() && $project->getGh_Editbot_Pass()) {
                // Try to perform the action on behalf of an authorized bot
                $gitHubBot = GithubFactory::getInstance($application, true, $project->getGh_Editbot_User(), $project->getGh_Editbot_Pass());
            }
        } catch (\RuntimeException $exception) {
            throw new \RuntimeException('Error retrieving an instance of the Github object', $exception->getCode(), $exception);
        }

        try {
            $gitHubResponse = $gitHub->issues->edit(
                $project->gh_user,
                $project->gh_project,
                $issueNumber,
                $state,
                $data['title'],
                $data['description_raw'],
                $assignee,
                $milestone,
                $labels
            );

            $needUpdate = false;
            $isAllowed  = $application->getUser()->check('manage');

            // The milestone and labels are silently dropped,
            // so try to update the milestone and/or labels if they are not set.
            if (
                (!empty($milestone) && empty($gitHubResponse->milestone)
                || (!empty($milestone) && $milestone != $gitHubResponse->milestone->number))
            ) {
                $needUpdate = true;
            } else {
                // Allow only specific group to reset milestone
                if (!empty($gitHubResponse->milestone) && $isAllowed) {
                    $milestone  = '';
                    $needUpdate = true;
                }
            }

            if (!empty($labels)) {
                if (empty($gitHubResponse->labels)) {
                    $needUpdate = true;
                }

                if (!empty($gitHubResponse->labels)) {
                    foreach ($gitHubResponse->labels as $ghLabel) {
                        // If labels differ then need to update
                        if (!\in_array($ghLabel->name, $labels)) {
                            $needUpdate = true;

                            break;
                        }
                    }
                }
            } else {
                // Allow only specific group to reset labels
                if (!empty($gitHubResponse->labels) && $isAllowed) {
                    $needUpdate = true;
                }
            }

            if ($needUpdate && isset($gitHubBot)) {
                $gitHubBot->issues->edit(
                    $project->gh_user,
                    $project->gh_project,
                    $gitHubResponse->number,
                    'open',
                    $data['title'],
                    $data['description_raw'],
                    $assignee,
                    $milestone,
                    $labels
                );
            }
        } catch (UnexpectedResponseException $exception) {
            // GitHub will return either a 403 or 404 in case there is a permission problem.
            if (!\in_array($exception->getResponse()->getStatusCode(), [403, 404])) {
                throw $exception;
            }

            if (!isset($gitHubBot)) {
                throw $exception;
            }

            $gitHubResponse = $gitHubBot->issues->edit(
                $project->gh_user,
                $project->gh_project,
                $issueNumber,
                $state,
                $data['title'],
                $data['description_raw'],
                $assignee,
                $milestone,
                $labels
            );

            $needUpdate = false;

            // The milestone and labels are silently dropped,
            // so try to update the milestone and/or labels if they are not set.
            if (
                (!empty($milestone) && empty($gitHubResponse->milestone)
                || (!empty($milestone) && $milestone != $gitHubResponse->milestone->number))
            ) {
                $needUpdate = true;
            } else {
                if (!empty($gitHubResponse->milestone)) {
                    $milestone  = '';
                    $needUpdate = true;
                }
            }

            if (!empty($labels)) {
                if (empty($gitHubResponse->labels)) {
                    $needUpdate = true;
                }

                if (!empty($gitHubResponse->labels)) {
                    foreach ($gitHubResponse->labels as $ghLabel) {
                        // If labels differ then need to update
                        if (!\in_array($ghLabel->name, $labels)) {
                            $needUpdate = true;

                            break;
                        }
                    }
                }
            } else {
                if (!empty($gitHubResponse->labels)) {
                    $needUpdate = true;
                }
            }

            // Try to update the milestone and/or labels
            if ($needUpdate) {
                $gitHubBot->issues->edit(
                    $project->gh_user,
                    $project->gh_project,
                    $gitHubResponse->number,
                    'open',
                    $data['title'],
                    $data['description_raw'],
                    $assignee,
                    $milestone,
                    $labels
                );
            }

            // Add a comment stating that this action has been performed by a MACHINE !!
            // (only if the "state" has changed - open <=> closed)
            if ($state != $oldState) {
                $uri = $application->get('uri')->base->full;

                $body = \sprintf(
                    'Set to "%s" on behalf of @%s by %s at %s',
                    $state,
                    $application->getUser()->username,
                    \sprintf('The <a href="%s">%s</a>', 'https://github.com/joomla/jissues', 'JTracker Application'),
                    \sprintf(
                        '<a href="%s">%s</a>',
                        $uri . 'tracker/' . $project->alias . '/' . $issueNumber,
                        str_replace(['http://', 'https://'], '', $uri) . $project->alias . '/' . $issueNumber
                    )
                );

                $gitHubBot->issues->comments->create(
                    $project->gh_user,
                    $project->gh_project,
                    $issueNumber,
                    $body
                );
            }
        }

        if (!isset($gitHubResponse->id)) {
            throw new \RuntimeException('Invalid response from GitHub');
        }

        return $gitHubResponse;
    }
}
