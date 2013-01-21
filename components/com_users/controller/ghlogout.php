<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Controller class to log out a user with GitHub.
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersControllerGhlogout extends JControllerTracker
{
	/**
	 * Method to log out a user using oAuth on GitHub.
	 *
	 * @throws DomainException
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since            1.0
	 */
	public function execute()
	{
		JGithubLoginhelper::clearCredentials();

		JFactory::getApplication()->redirect(JRoute::_('index.php', false));

		return true;
	}
}
