<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller class to remind a user of their login information.
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersControllerRemind extends JControllerTracker
{
	/**
	 * Method to request a username reminder.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Check the request token.
		JSession::checkToken('post') or jexit(JText::_('JINVALID_TOKEN'));

		$app = $this->getApplication();

		$model = new UsersModelRemind;
		$data  = $this->input->post->get('jform', array(), 'array');

		// Get the route to the next page.
		$itemid = UsersHelperRoute::getRemindRoute();
		$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';

		try
		{
			// Submit the password reset request.
			$model->processRemindRequest($data);

			// The request succeeded.
			$route = 'index.php?option=com_users&view=login' . $itemid;

			// Proceed to step two.
			$app->enqueueMessage(JText::_('COM_USERS_REMIND_REQUEST_SUCCESS'));
		}
		catch (Exception $e)
		{
			// The request failed.
			$route = 'index.php?option=com_users&view=remind' . $itemid;

			$app->enqueueMessage($e->getMessage(), 'error');
		}

		$app->redirect(JRoute::_($route, false));

		return false;
	}
}
