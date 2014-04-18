<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;

use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Github\Exception\GithubException;
use JTracker\GitHub\Github;

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

		try
		{
			$user->authorize('edit');

			// The user has full edit rights
			$data = $src;
		}
		catch (AuthenticationException $e)
		{
			// Check "edit own"
			if (false == $user->canEditOwn($item->opened_by))
			{
				throw $e;
			}

			// The user only has "edit own" rights.
			$data['id'] = (int) $src['id'];
			$data['issue_number'] = (int) $src['issue_number'];

			$data['title'] = $src['title'];
			$data['description_raw'] = $src['description_raw'];

			// Take the remaining values from the stored item
			$data['status']          = $item->status;
			$data['priority']        = $item->priority;
			$data['build']           = $item->build;
			$data['rel_number']      = $item->rel_number;
			$data['rel_type']        = $item->rel_type;
			$data['easy']            = $item->easy;
			$data['tests']           = $item->tests;
		}

		/* @type \Joomla\Github\Github $gitHub */
		$gitHub = $this->getContainer()->get('gitHub');

		if ($project->gh_user && $project->gh_project)
		{
			// Project is managed on GitHub

			// Check if the state has changed (e.g. open/closed)
			$oldState = $model->getOpenClosed($item->status);
			$state    = $model->getOpenClosed($data['status']);

			try
			{
				// Try to update the project on GitHub using thew current user credentials
				$gitHubResponse = $gitHub->issues->edit(
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
				$gitHubBot = new Github;

				$gitHubBot->setOption('api.username', $project->getGh_Editbot_User());
				$gitHubBot->setOption('api.password', $project->getGh_Editbot_Pass());

				// Update the project on GitHub
				$gitHubResponse = $gitHubBot->issues->edit(
					$project->gh_user, $project->gh_project,
					$issueNumber, $state, $data['title'], $data['description_raw']
				);

				// Add a comment stating that this action has been performed by a MACHINE !!
				// (only if the "state" has change - open/close)
				if ($state != $oldState)
				{
					$body = sprintf(
						'Modified on behalf of @%s by %s',
						$user->username,
						sprintf(
							'The <a href="%s">%s</a>',
							'https://github.com/joomla/jissues',
							'JTracker Application'
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
		}

		try
		{
			// Save the record.
			$model->save($data);

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

		parent::execute();
	}
}
