<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Controller\User;

use App\Users\Model\UserModel;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class to save an item via the users component.
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
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		/* @type \JTracker\Application $application */
		$application = $this->container->get('app');

		$src = $application->input->get('item', array(), 'array');

		if (!$src['id'])
		{
			throw new \UnexpectedValueException('No id given');
		}

		if (!$application->getUser()->authorize('admin'))
		{
			if ($application->getUser()->id != $src['id'])
			{
				$application->enqueueMessage(
					g11n3t('You are not authorized to edit this user.'), 'error'
				);

				$application->redirect(
						$application->get('uri.base.path') . 'user/' . $src['id']
					);
			}
		}

		try
		{
			// Save the record.
			(new UserModel($this->container->get('db')))->save($src);

			$application->enqueueMessage(
				g11n3t('The changes have been saved.'), 'success'
			);
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');
		}

		$application->redirect(
			$application->get('uri.base.path') . 'user/' . $src['id'] . '/edit'
		);

		parent::execute();
	}
}
