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

use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Github\Exception\GithubException;
use JTracker\Github\GithubFactory;

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
	 * @throws \Exception
	 * @throws \JTracker\Authentication\Exception\AuthenticationException
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$src = $application->input->get('item', array(), 'array');

		$user = $application->getUser();
		$project = $application->getProject();

		$model = new IssueModel($this->getContainer()->get('db'));
		$model->setProject($project);

		$issueNumber = isset($src['issue_number']) ? (int) $src['issue_number'] : 0;

		if (!$issueNumber)
		{
			throw new \UnexpectedValueException('No issue number received.');
		}

		$item = $model->getItem($issueNumber);

		$data = array();

		if ($user->check('edit'))
		{
			// The user has full "edit" permission.
			$data = $src;
		}
		elseif($user->canEditOwn($item->opened_by))
		{
			// The user has "edit own" permission.
			$data['id']              = (int) $src['id'];
			$data['issue_number']    = (int) $src['issue_number'];
			$data['title']           = $src['title'];
			$data['description_raw'] = $src['description_raw'];

			// Take the remaining values from the stored item
			$data['status']          = $item->status;
			$data['priority']        = $item->priority;
			$data['build']           = $item->build;
			$data['rel_number']      = $item->rel_number;
			$data['rel_type']        = $item->rel_type;
			$data['easy']            = $item->easy;
		}
		else
		{
			// The user has no "edit" permission.
			throw new AuthenticationException($user, 'edit');
		}

		$gitHub = GithubFactory::getInstance($application);

		// Check if the state has changed (e.g. open/closed)
		$oldState = $model->getOpenClosed($item->status);
		$state    = $model->getOpenClosed($data['status']);

		if ($project->gh_user && $project->gh_project)
		{
			// Project is managed on GitHub

			try
			{
				$gitHubResponse = $this->updateGitHub($item->issue_number, $data, $state, $oldState);

				// Set the modified_date from GitHub (important!)
				$data['modified_date'] = $gitHubResponse->updated_at;
			}
			catch (GithubException $exception)
			{
				// DEBUG - @todo remove in stable
				$dump = [
					'exception' => $exception->getCode() . ' ' . $exception->getMessage(),
					'user' => $project->gh_user, 'project' => $project->gh_project,
					'issueNo' => $item->issue_number,
					'state' => $state, 'old_state' => $oldState,
					'data' => $data
				];

				var_dump($dump);

				die('Unrecoverable GitHub error - sry ;(');
			}

			// Render the description text using GitHub's markdown renderer.
			$data['description'] = $gitHub->markdown->render(
				$data['description_raw'], 'gfm',
				$project->gh_user . '/' . $project->gh_project
			);
		}
		else
		{
			// Project is managed by JTracker only

			// Render the description text using GitHub's markdown renderer.
			$data['description'] = $gitHub->markdown->render($src['description_raw'], 'markdown');

			$data['modified_date'] = (new Date)->format($this->getContainer()->get('db')->getDateFormat());
		}

		try
		{
			$data['modified_by'] = $user->username;

			// If the user have edit permission, let him / her modify the categories.
			if ($user->check('edit'))
			{
				$categoryModel            = new CategoryModel($this->getContainer()->get('db'));
				$category['issue_id']     = $data['id'];
				$category['modified_by']  = $user->username;
				$category['categories']   = $application->input->get('categories', null, 'array');
				$category['issue_number'] = $data['issue_number'];
				$category['project_id']   = $project->project_id;

				$categoryModel->updateCategory($category);
			}

			// Pass the old and new states into the save method
			$data['old_state'] = $oldState;
			$data['new_state'] = $state;

			// Save the record.
			$model->save($data);

			$comment = $application->input->get('comment', '', 'raw');

			// Save the comment.
			if ($comment)
			{
				$project = $application->getProject();

				$comment .= sprintf(
					'<hr /><sub>This comment was created with the <a href="%1$s">%2$s Application</a> at <a href="%3$s">%4$s</a>.</sub>',
					'https://github.com/joomla/jissues', 'J!Tracker',
					$application->get('uri')->base->full . 'tracker/' . $project->alias . '/' . $issueNumber,
					str_replace(['http://', 'https://'], '', $application->get('uri')->base->full) . $project->alias . '/' . $issueNumber
				);

				/* @type \Joomla\Github\Github $github */
				$github = $this->getContainer()->get('gitHub');

				$data = new \stdClass;
				$db   = $this->getContainer()->get('db');

				if ($project->gh_user && $project->gh_project)
				{
					$gitHubResponse = $github->issues->comments->create(
						$project->gh_user, $project->gh_project, $issueNumber, $comment
					);

					if (!isset($gitHubResponse->id))
					{
						throw new \Exception('Invalid response from GitHub');
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
				}
				else
				{
					$date = new Date;

					$data->created_at = $date->format($db->getDateFormat());
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
				->redirect(
				'/tracker/' . $application->input->get('project_alias') . '/' . $issueNumber
			);
		}
		catch (\Exception $exception)
		{
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
	 *
	 * @throws \Exception
	 * @throws \JTracker\Github\Exception\GithubException
	 *
	 *
	 * @return  object
	 *
	 * @since   1.0
	 */
	private function updateGitHub($issueNumber, array $data, $state, $oldState)
	{
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$project = $application->getProject();

		try
		{
			// Try to update the project on GitHub using thew current user credentials
			$gitHubResponse = GithubFactory::getInstance($application)->issues->edit(
				$project->gh_user, $project->gh_project,
				$issueNumber, $state, $data['title'], $data['description_raw']
			);
		}
		catch (GithubException $exception)
		{
			// GitHub will return a "404 - not found" in case there is a permission problem.
			if (404 != $exception->getCode())
			{
				throw $exception;
			}

			// Look if we have a bot user configured.
			if (!$project->getGh_Editbot_User() || !$project->getGh_Editbot_Pass())
			{
				throw $exception;
			}

			// Try to perform the action on behalf of an authorized bot.
			$gitHubBot = GithubFactory::getInstance($application, true, $project->getGh_Editbot_User(), $project->getGh_Editbot_Pass());

			// Update the project on GitHub
			$gitHubResponse = $gitHubBot->issues->edit(
				$project->gh_user, $project->gh_project,
				$issueNumber, $state, $data['title'], $data['description_raw']
			);

			// Add a comment stating that this action has been performed by a MACHINE !!
			// (only if the "state" has changed - open <=> closed)
			if ($state != $oldState)
			{
				$uri = $application->get('uri')->base->full;

				$body = sprintf(
					'Set to "%s" on behalf of @%s by %s at %s',
					$state,
					$application->getUser()->username,
					sprintf('The <a href="%s">%s</a>', 'https://github.com/joomla/jissues', 'JTracker Application'),
					sprintf(
						'<a href="%s">%s</a>',
						$uri . 'tracker/' . $project->alias . '/' . $issueNumber,
						str_replace(['http://', 'https://'], '', $uri) . $project->alias . '/' . $issueNumber
					)
				);

				$gitHubBot->issues->comments->create(
					$project->gh_user, $project->gh_project,
					$issueNumber, $body
				);
			}
		}

		if (!isset($gitHubResponse->id))
		{
			throw new \Exception('Invalid response from GitHub');
		}

		return $gitHubResponse;
	}
}
