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
		/** @var \JTracker\Application $app */
		$app = $this->getContainer()->get('app');

		$id = $app->getUser()->id;

		if (!$id)
		{
			throw new \UnexpectedValueException('Not authenticated.');
		}

		$src = $app->input->get('item', [], 'array');
		$src['id'] = $id;

		try
		{
			// Save the record.
			(new UserModel($this->getContainer()->get('db')))->save($src);

			$app->enqueueMessage('The changes have been saved.', 'success');
		}
		catch (\Exception $e)
		{
			$app->enqueueMessage($e->getMessage(), 'error');
		}

		$app->redirect($app->get('uri.base.path') . 'account/edit');

		// To silence PHPCS expecting a return
		return '';
	}
}
