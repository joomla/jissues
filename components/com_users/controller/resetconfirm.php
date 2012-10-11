<?php
/**
 * User: elkuku
 * Date: 09.10.12
 * Time: 21:28
 */
class UsersControllerResetconfirm extends JControllerBase
{
	/**
	 * Method to confirm the password request.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 */
	public function execute()
	{
		// Check the request token.
		JSession::checkToken('request') or jexit(JText::_('JINVALID_TOKEN'));

		$app = JFactory::getApplication();
		$model = $this->getModel('Reset', 'UsersModel');
		$data = $this->input->get('jform', array(), 'array');

		// Confirm the password reset request.
		$return = $model->processResetConfirm($data);

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
				$message = JText::_('COM_USERS_RESET_CONFIRM_ERROR');
			}

			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset&layout=confirm' . $itemid;

			// Go back to the confirm form.
			$this->setRedirect(JRoute::_($route, false), $message, 'error');
			return false;
		}
		elseif ($return === false)
		{
			// Confirm failed.
			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset&layout=confirm' . $itemid;

			// Go back to the confirm form.
			$message = JText::sprintf('COM_USERS_RESET_CONFIRM_FAILED', $model->getError());
			$this->setRedirect(JRoute::_($route, false), $message, 'notice');
			return false;
		}
		else
		{
			// Confirm succeeded.
			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset&layout=complete' . $itemid;

			// Proceed to step three.
			$this->setRedirect(JRoute::_($route, false));
			return true;
		}
	}


}
