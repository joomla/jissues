<?php
/**
 * User: elkuku
 * Date: 09.10.12
 * Time: 21:28
 */

class UsersControllerResetrequest extends JControllerBase
{
	/**
	 * Method to request a password reset.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 */
	public function execute()
	{
		// Check the request token.
		JSession::checkToken('post') or jexit(JText::_('JINVALID_TOKEN'));

		$app   = $this->getApplication();
		$model = new UsersModelReset;
		$data  = $this->input->post->get('jform', array(), 'array');

		try
		{
			$model->processResetRequest($data);

			// The request succeeded.
			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset&layout=confirm' . $itemid;

			// Proceed to step two.
			$app->redirect(JRoute::_($route, false));

		}
		catch (Exception $e)
		{
			// The request failed.
			// Get the route to the next page.
			$itemid = UsersHelperRoute::getResetRoute();
			$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
			$route = 'index.php?option=com_users&view=reset' . $itemid;

			$app->enqueueMessage($e->getMessage(), 'error');

			// Go back to the request form.
			$app->redirect(JRoute::_($route, false));
		}

		return true;
	}
}
