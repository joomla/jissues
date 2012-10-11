<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Reset controller class for Users.
 *
 * @package     Joomla.Site
 * @subpackage  com_users
 * @since       1.6
 */
class UsersControllerRemind extends JControllerBase
{
	/**
	 * Method to request a username reminder.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 */
	public function execute()
	{
		// Check the request token.
		JSession::checkToken('post') or jexit(JText::_('JINVALID_TOKEN'));

		$application = JFactory::getApplication();

		$model = new UsersModelRemind;
		$data = $this->input->post->get('jform', array(), 'array');

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
			$application->enqueueMessage(JText::_('COM_USERS_REMIND_REQUEST_SUCCESS'));
		}
		catch (Exception $e)
		{
			// The request failed.
			$route = 'index.php?option=com_users&view=remind' . $itemid;

			$application->enqueueMessage($e->getMessage(), 'error');
		}

		$application->redirect(JRoute::_($route, false));

		return false;
	}
}
