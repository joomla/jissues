<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Helper;

use App\Projects\TrackerProject;
use App\Tracker\Model\ActivityModel;
use Joomla\Database\DatabaseDriver;
use JTracker\Application\Application;
use JTracker\Github\DataType\Commit;
use JTracker\Github\DataType\Commit\CombinedStatus;
use JTracker\Github\DataType\Commit\Status;
use JTracker\Github\DataType\JTracker\Issues\Comment;
use JTracker\Github\Github;

/**
 * Helper class containing methods for working with GitHub.
 *
 * @since  1.0
 */
class GitHubHelper
{
    /**
     * @var Github
     */
    private $gitHub;

    /**
     * Constructor.
     *
     * @param   Github  $gitHub  The GitHub object.
     */
    public function __construct($gitHub)
    {
        $this->gitHub = $gitHub;
    }

    /**
     * Add a comment on GitHub.
     *
     * @param   TrackerProject  $project      The project.
     * @param   integer         $issueNumber  The issue number.
     * @param   string          $comment      The comment to add.
     * @param   string          $userName     The username.
     * @param   DatabaseDriver  $database     The database driver object.
     *
     * @return  Comment  The GitHub comment object
     *
     * @throws \DomainException
     *
     * @since  1.0
     */
    public function addComment(TrackerProject $project, $issueNumber, $comment, $userName, DatabaseDriver $database)
    {
        $data = new Comment();

        if ($project->gh_user && $project->gh_project) {
            $gitHubResponse = $this->gitHub->issues->comments->create(
                $project->gh_user,
                $project->gh_project,
                $issueNumber,
                $comment
            );

            if (!isset($gitHubResponse->id)) {
                throw new \DomainException('Invalid response from GitHub');
            }

            $data->created_at = $gitHubResponse->created_at;
            $data->opened_by  = $gitHubResponse->user->login;
            $data->comment_id = $gitHubResponse->id;
            $data->text_raw   = $gitHubResponse->body;

            $data->text = $this->gitHub->markdown->render(
                $comment,
                'gfm',
                $project->gh_user . '/' . $project->gh_project
            );
        } else {
            $data->created_at = (new \DateTime())->format($database->getDateFormat());
            $data->opened_by  = $userName;
            $data->comment_id = '???';
            $data->text_raw   = $comment;
            $data->text       = $this->gitHub->markdown->render($comment, 'markdown');
        }

        (new ActivityModel($database))
            ->addActivityEvent(
                'comment',
                $data->created_at,
                $data->opened_by,
                $project->project_id,
                $issueNumber,
                $data->comment_id,
                $data->text,
                $data->text_raw
            );

        $data->activities_id = $database->insertid();

        $data->created_at = (new \DateTime($data->created_at))->format('j M Y');

        return $data;
    }

    /**
     * Get the commits for a GitHub pull request.
     *
     * @param   TrackerProject  $project      The project object.
     * @param   integer         $issueNumber  The issue number.
     *
     * @return  Commit[]
     *
     * @since   1.0
     */
    public function getCommits(TrackerProject $project, $issueNumber)
    {
        $commits = [];

        $commitData = $this->gitHub->pulls->getCommits(
            $project->gh_user,
            $project->gh_project,
            $issueNumber
        );

        foreach ($commitData as $commit) {
            $c = new Commit();

            $c->sha            = $commit->sha;
            $c->message        = $commit->commit->message;
            $c->author_name    = $commit->author->login ?? '';
            $c->author_date    = $commit->commit->author->date;
            $c->committer_name = $commit->committer->login ?? '';
            $c->committer_date = $commit->commit->committer->date;

            $commits[] = $c;
        }

        return $commits;
    }

    /**
     * Get the combined GitHub merge status for an issue.
     *
     * @param   TrackerProject  $project  The project object.
     * @param   string          $ref      Ref to fetch the status for. It can be a SHA, a branch name, or a tag name.
     *
     * @return  CombinedStatus
     *
     * @since    1.0
     */
    public function getCombinedStatus(TrackerProject $project, $ref)
    {
        $combinedStatus = new CombinedStatus();

        $combined = $this->gitHub->repositories->statuses->getCombined(
            $project->gh_user,
            $project->gh_project,
            $ref
        );

        $combinedStatus->state = $combined->state;

        foreach ($combined->statuses as $status) {
            $s = new Status();

            $s->state       = $status->state;
            $s->targetUrl   = $status->target_url;
            $s->description = $status->description;
            $s->context     = $status->context;

            $combinedStatus->statuses[] = $s;
        }

        return $combinedStatus;
    }

    /**
     * Create a GitHub merge status for the last commit in a PR.
     *
     * @param   TrackerProject  $project      The project object.
     * @param   integer         $issueNumber  The issue number.
     * @param   string          $state        The state (pending, success, error or failure).
     * @param   string          $targetUrl    Optional target URL.
     * @param   string          $description  Optional description for the status.
     * @param   string          $context      A string label to differentiate this status from the status of other systems.
     * @param   string          $sha          The SHA for the commit.
     *
     * @return  Status
     *
     * @since   1.0
     */
    public function createStatus(TrackerProject $project, $issueNumber, $state, $targetUrl, $description, $context, $sha = '')
    {
        if (!$sha) {
            $pullRequest = $this->gitHub->pulls->get(
                $project->gh_user,
                $project->gh_project,
                $issueNumber
            );

            $sha = $pullRequest->head->sha;
        }

        return $this->gitHub->repositories->statuses->create(
            $project->gh_user,
            $project->gh_project,
            $sha,
            $state,
            $targetUrl,
            $description,
            $context
        );
    }

    /**
     * Get a string for usage in comment footers.
     *
     * @param   Application     $application  The application object.
     * @param   TrackerProject  $project      The project object.
     * @param   integer         $issueNumber  The issue number.
     *
     * @since   1.0
     * @return string
     */
    public function getApplicationComment(Application $application, TrackerProject $project, $issueNumber)
    {
        return \sprintf(
            '<hr /><sub>This comment was created with the <a href="%1$s">%2$s Application</a> at <a href="%3$s">%4$s</a>.</sub>',
            'https://github.com/joomla/jissues',
            'J!Tracker',
            $application->get('uri')->base->full . 'tracker/' . $project->alias . '/' . $issueNumber,
            str_replace(['http://', 'https://'], '', $application->get('uri')->base->full) . 'tracker/' . $project->alias . '/' . $issueNumber
        );
    }
}
