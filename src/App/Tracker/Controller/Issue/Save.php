<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Issue;

use App\Tracker\Model\IssueModel;

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
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		$application->getUser()->authorize('edit');

		$src = $application->input->get('item', array(), 'array');

		try
		{
			// Save the record.
			(new IssueModel($this->container->get('db')))
				->save($src);

			$application->enqueueMessage('The changes have been saved.', 'success')
				->redirect(
				'/tracker/' . $application->input->get('project_alias')
			);
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');

			if (!empty($src['id']))
			{
				$application->redirect(
					$application->get('uri.base.path')
					. 'tracker/' . $application->input->get('project_alias') . '/' . $src['id'] . '/edit'
				);
			}
			else
			{
				$application->redirect(
					$application->get('uri.base.path')
					. 'tracker/' . $application->input->get('project_alias')
				);
			}
		}

		parent::execute();
	}
}
