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

/**
 * Controller class to save an item via the tracker component.
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
		$data['id'] = (int) $src['id'];
		$data['issue_number'] = (int) $src['issue_number'];

		try
		{
			$user->authorize('edit');

			// The user has full edit rights
			$data = $src;
		}
		catch (AuthenticationException $e)
		{
			if (false == $user->canEditOwn($item->opened_by))
			{
				throw $e;
			}

			// The user only has "edit own" rights.
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
			$state = null;

			// Update the project on GitHub
			$gitHubResponse = $gitHub->issues->edit(
				$project->gh_user, $project->gh_project,
				$issueNumber, $state, $data['title'], $data['description_raw']
			);

			if (!isset($gitHubResponse->id))
			{
				throw new \Exception('Invalid response from GitHub');
			}

			$data['description'] = $gitHub->markdown->render(
				$data['description_raw'], 'gfm',
				$project->gh_user . '/' . $project->gh_project
			);
		}
		else
		{
			// Project is managed by JTracker only
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
