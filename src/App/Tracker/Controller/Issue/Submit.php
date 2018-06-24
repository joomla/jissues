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

use Joomla\Date\Date;

use Joomla\Http\Exception\UnexpectedResponseException;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Github\GithubFactory;

/**
 * Add issues controller class.
 *
 * @since  1.0
 */
class Submit extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$user = $application->getUser();

		$user->authorize('create');

		/** @var \Joomla\Github\Github $gitHub */
		$gitHub = $this->getContainer()->get('gitHub');

		$project = $application->getProject();

		$body = $application->input->get('body', '', 'raw');

		if (!$body)
		{
			throw new \RuntimeException('No body received.');
		}

		// Prepare issue for the store
		$data = [];

		$data['title']        = $application->input->getString('title');
		$data['milestone_id'] = $application->input->getInt('milestone_id');

		// Process labels
		$labels = [];

		foreach ($application->input->get('labels', [], 'array') as $labelId)
		{
			// Filter integer
			$labels[] = (int) $labelId;
		}

		/**
		 * Store the "No code attached yet" label for CMS issue
		 * @todo Remove after #596 is implemented
		 */
		if ($project->project_id == 1 && !in_array(39, $labels))
		{
			$labels[] = 39;
		}

		$data['labels'] = implode(',', $labels);

		$issueModel = new IssueModel($this->getContainer()->get('db'));
		$issueModel->setProject($project);

		// Project is managed on GitHub
		if ($project->gh_user && $project->gh_project)
		{
			// @todo assignee
			$assignee = null;

			// Prepare labels
			$ghLabels = [];

			if (!empty($labels))
			{
				foreach ($project->getLabels() as $id => $label)
				{
					if (in_array($id, $labels))
					{
						$ghLabels[] = $label->name;
					}
				}
			}

			// Prepare milestone
			$ghMilestone = null;

			if (!empty($data['milestone_id']))
			{
				foreach ($project->getMilestones() as $milestone)
				{
					if ($milestone->milestone_id == $data['milestone_id'])
					{
						$ghMilestone = $milestone->milestone_number;
					}
				}
			}

			$gitHubResponse = $this->updateGitHub(
				$data['title'], $body, $assignee, $ghMilestone, $ghLabels
			);

			$data['opened_date']   = $gitHubResponse->created_at;
			$data['modified_date'] = $gitHubResponse->created_at;
			$data['opened_by']     = $gitHubResponse->user->login;
			$data['modified_by']   = $gitHubResponse->user->login;
			$data['number']        = $gitHubResponse->number;

			$data['description'] = $gitHub->markdown->render(
				$body, 'gfm',
				$project->gh_user . '/' . $project->gh_project
			);
		}
		// Project is managed by JTracker only
		else
		{
			$data['opened_date']    = (new Date)->format($this->getContainer()->get('db')->getDateFormat());
			$data['modified_date']  = (new Date)->format($this->getContainer()->get('db')->getDateFormat());
			$data['opened_by']      = $user->username;
			$data['modified_by']    = $user->username;
			$data['number']         = $issueModel->getNextNumber();
			$data['description']    = $gitHub->markdown->render($body, 'markdown');
		}

		$data['priority']        = $application->input->getInt('priority');
		$data['build']           = $application->input->getString('build');
		$data['project_id']      = $project->project_id;
		$data['issue_number']    = $data['number'];
		$data['description_raw'] = $body;

		// On submit the state is allways open; see #862
		$data['new_state'] = 'open';
		$data['old_state'] = 'open';

		// Store the issue
		try
		{
			// Save the issues and Get the issue id from model state
			$issue_id = $issueModel->add($data)->getState()->get('issue_id');

			// Save the category for the issue
			$category = [
				'issue_id'   => $issue_id,
				'categories' => $application->input->get('categories', null, 'array'),
			];

			(new CategoryModel($this->getContainer()->get('db')))->saveCategory($category);
		}
		catch (\RuntimeException $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');

			$application->redirect(
				$application->get('uri.base.path')
				. 'tracker/' . $project->alias . '/add'
			);
		}

		$application->enqueueMessage(g11n3t('Your report has been submitted.'), 'success');

		$application->redirect(
			$application->get('uri.base.path')
			. 'tracker/' . $project->alias . '/' . $data['number']
		);

		return;
	}

	/**
	 * Update the issue on GitHub.
	 *
	 * The method will first try to perform the action with the logged in user credentials and then, if it fails, perform
	 * the action using a configured "edit bot". If the GitHub status changes (e.g. open <=> close), a comment will be
	 * created automatically stating that the action has been performed by a bot.
	 *
	 * @param   string   $title      The title of the issue.
	 * @param   string   $body       The contents of the issue.
	 * @param   string   $assignee   The login for the GitHub user that this issue should be assigned to.
	 * @param   integer  $milestone  The milestone to associate this issue with.
	 * @param   array    $labels     The labels to associate with this issue.
	 *
	 * @return  object  The issue data
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function updateGitHub($title, $body, $assignee, $milestone, $labels)
	{
		/** @var \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$project = $application->getProject();

		try
		{
			// Try to perform the action on behalf of current user
			$gitHub = GithubFactory::getInstance($application);

			// Look if we have a bot user configured
			if ($project->getGh_Editbot_User() && $project->getGh_Editbot_Pass())
			{
				// Try to perform the action on behalf of an authorized bot
				$gitHubBot = GithubFactory::getInstance($application, true, $project->getGh_Editbot_User(), $project->getGh_Editbot_Pass());
			}
		}
		catch (\RuntimeException $exception)
		{
			throw new \RuntimeException('Error retrieving an instance of the Github object', $exception->getCode(), $exception);
		}

		try
		{
			$gitHubResponse = $gitHub->issues->create(
				$project->gh_user, $project->gh_project,
				$title, $body, $assignee, $milestone, $labels
			);
		}
		catch (UnexpectedResponseException $exception)
		{
			$this->getContainer()->get('app')->getLogger()->error(
				sprintf(
					'Error code %1$s received from GitHub when creating an issue with the following data:'
					. ' GitHub User: %2$s; GitHub Repo: %3$s; Title: %4$s; Body Text: %5$s',
					$exception->getCode(),
					$project->gh_user,
					$project->gh_project,
					$title,
					$body
				),
				['exception' => $exception, 'response' => $exception->getResponse()->body]
			);

			throw new \RuntimeException('Invalid response from GitHub', $exception->getCode(), $exception);
		}

		/**
		 * Try to update the milestone and/or labels.
		 * We are not throwing any error because the issue is already created.
		 */
		if (isset($gitHubBot))
		{
			if ((!empty($milestone) && empty($gitHubResponse->milestone))
				|| (!empty($labels) && empty($gitHubResponse->labels)))
			{
				$gitHubBot->issues->edit(
					$project->gh_user, $project->gh_project,
					$gitHubResponse->number, 'open', $title, $body,
					$assignee, $milestone, $labels
				);
			}
		}

		return $gitHubResponse;
	}
}
