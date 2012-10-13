<?php
/**
 * User: elkuku
 * Date: 09.10.12
 * Time: 21:29
 */

class UsersControllerResetcomplete extends JControllerBase
{
	/**
	 * Method to complete the password reset process.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Check for request forgeries
		JSession::checkToken('post') or jexit(JText::_('JINVALID_TOKEN'));

		$app   = $this->getApplication();
		$model = new UsersModelReset;
		$data  = $this->input->post->get('jform', array(), 'array');

		// Complete the password reset request.
		$return = $model->processResetComplete($data);

		// Check for a hard error.
		if ($return instanceof Exception)
		{
			// Get the error message to display.
			if ($app->getCfg('error_reporting'))
			{
				$message = $return->getMessage();
			}
			else
			{
				$message = JText::_('COM_USERS_RESET_COMPLETE_ERROR');
			}
			$app->enqueueMessage($message, 'error');

			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset&layout=complete' . $itemid;

			// Go back to the complete form.
			$app->redirect(JRoute::_($route, false));
			return false;
		}
		elseif ($return === false)
		{
			// Complete failed.
			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset&layout=complete' . $itemid;

			// Go back to the complete form.
			$app->redirect(JRoute::_($route, false));
			return false;
		}
		else
		{
			// Complete succeeded.
			// Get the route to the next page.
			$itemid = UsersHelperRoute::getLoginRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=login' . $itemid;

			// Proceed to the login form.
			$app->enqueueMessage(JText::_('COM_USERS_RESET_COMPLETE_SUCCESS'));
			$app->redirect(JRoute::_($route, false));
			return true;
		}
	}
}
