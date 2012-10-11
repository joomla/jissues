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
 * Remind model class for Users.
 *
 * @package     Joomla.Site
 * @subpackage  com_users
 * @since       1.5
 */
class UsersModelRemind extends JModelTrackerform
{
	/**
	 * Instantiate the model.
	 *
	 * @param   JRegistry  $state  The model state.
	 *
	 * @since   12.1
	 */
	public function __construct(JRegistry $state = null)
	{
		// Setup the model.
		$this->state = is_null($state) ? $this->loadState() : $state;

		// @todo JModelBase incorrectly resets the state in its __construct()or :(
		parent::__construct($state);

		$params = JFactory::getApplication()->getParams('com_users');

		// Load the parameters.
		$this->state->set('com_users.params', $params);
	}

	/**
	 * Method to get the username remind request form.
	 *
	 * @param    array      $data        An optional array of data for the form to interogate.
	 * @param    boolean    $loadData    True if the form is to load its own data (default case), false if not.
	 *
	 * @return    JForm    A JForm object on success, false on failure
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_users.remind', 'remind', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Override preprocessForm to load the user plugin group instead of content.
	 *
	 * @param   JForm   $form   A form object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $group  The group.
	 *
	 *
	 * @return void
	 * @since    1.6
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'user')
	{
		parent::preprocessForm($form, $data, 'user');
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since    1.6
	 */
	protected function populateState()
	{
		// Get the application object.
		$app = JFactory::getApplication();
		$params = $app->getParams('com_users');

		// Load the parameters.
		$this->setState('params', $params);
	}

	/**
	 * @since    1.6
	 */
	public function processRemindRequest($data)
	{
		$application = JFactory::getApplication();

		// Get the form.
		$form = $this->getForm();

		// Check for an error.
		if (empty($form))
		{
			return false;
		}

		// Validate the data.
		$data = $this->validate($form, $data);

		// Check for an error.
		if ($data instanceof Exception)
		{
			throw $data; //:P
//			return $return;
		}

		// Check the validation results.
		if ($data === false)
		{

			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$application->enqueueMessage($message, 'warning');
//				$this->setError($message);
			}

			return false;
		}

		// Find the user id for the given email address.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('email') . ' = ' . $db->Quote($data['email']));

		// Get the user id.
		$db->setQuery((string) $query);

		$user = $db->loadObject();

		// Check for a user.
		if (empty($user))
		{

			throw new RuntimeException(JText::_('COM_USERS_USER_NOT_FOUND'));
		}

		// Make sure the user isn't blocked.
		if ($user->block)
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_BLOCKED'));
		}

		$config = JFactory::getConfig();

		// Assemble the login link.
		$itemid = UsersHelperRoute::getLoginRoute();
		$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
		$link = 'index.php?option=com_users&view=login' . $itemid;
		$mode = $config->get('force_ssl', 0) == 2 ? 1 : -1;

		// Put together the email template data.
		$data = JArrayHelper::fromObject($user);
		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['link_text'] = JRoute::_($link, false, $mode);
		$data['link_html'] = JRoute::_($link, true, $mode);

		$subject = JText::sprintf(
			'COM_USERS_EMAIL_USERNAME_REMINDER_SUBJECT',
			$data['sitename']
		);
		$body = JText::sprintf(
			'COM_USERS_EMAIL_USERNAME_REMINDER_BODY',
			$data['sitename'],
			$data['username'],
			$data['link_text']
		);

		// Send the password reset request email.
		$return = JFactory::getMailer()->sendMail($data['mailfrom'], $data['fromname'], $user->email, $subject, $body);

		// Check for an error.
		if ($return !== true)
		{
			throw new RuntimeException(JText::_('COM_USERS_MAIL_FAILED'));
			//$this->setError(JText::_('COM_USERS_MAIL_FAILED'), 500);
			//return false;
		}

		return true;
	}
}
