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
 * Rest model class for Users.
 *
 * @package     Joomla.Site
 * @subpackage  com_users
 * @since       1.5
 */
class UsersModelReset extends JModelTrackerform
{
	/**
	 * Method to get the password reset request form.
	 *
	 * @param    array    $data      An optional array of data for the form to interogate.
	 * @param    boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return   JForm  A JForm object on success, false on failure
	 * @since    1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_users.reset_request', 'reset_request', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the password reset complete form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getResetCompleteForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_users.reset_complete', 'reset_complete', $options = array('control' => 'jform'));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the password reset confirm form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  JForm  A JForm object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getResetConfirmForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_users.reset_confirm', 'reset_confirm', $options = array('control' => 'jform'));
		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to preprocess the form.
	 *
	 * @param   JForm   $form   A JForm object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import (defaults to "content").
	 *
	 * @return  void
	 *
	 * @see     JFormField
	 * @since   1.0
	 * @throws  Exception if there is an error in the form event.
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'user')
	{
		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * @since    1.6
	 */
	function processResetComplete($data)
	{
		// Get the form.
		$form = $this->getResetCompleteForm();

		// Check for an error.
		if ($form instanceof Exception)
		{
			return $form;
		}

		// Filter and validate the form data.
		$data = $form->filter($data);
		$return = $form->validate($data);

		// Check for an error.
		if ($return instanceof Exception)
		{
			return $return;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				JFactory::getApplication()->enqueueMessage($message, 'error');
			}

			return false;
		}

		// Get the token and user id from the confirmation process.
		$app = JFactory::getApplication();
		$token = $app->getUserState('com_users.reset.token', null);
		$userId = $app->getUserState('com_users.reset.user', null);

		// Check the token and user id.
		if (empty($token) || empty($userId))
		{
			return new JException(JText::_('COM_USERS_RESET_COMPLETE_TOKENS_MISSING'), 403);
		}

		// Get the user object.
		$user = JUser::getInstance($userId);

		// Check for a user and that the tokens match.
		if (empty($user) || $user->activation !== $token)
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_NOT_FOUND'));
		}

		// Make sure the user isn't blocked.
		if ($user->block)
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_BLOCKED'));
		}

		// Generate the new password hash.
		$salt     = JUserHelper::genRandomPassword(32);
		$crypted  = JUserHelper::getCryptedPassword($data['password1'], $salt);
		$password = $crypted . ':' . $salt;

		// Update the user object.
		$user->password = $password;
		$user->activation = '';
		$user->password_clear = $data['password1'];

		// Save the user to the database.
		if (!$user->save(true))
		{
			return new RuntimeException(
				JText::sprintf('COM_USERS_USER_SAVE_FAILED', $user->getError())
				, 500
			);
		}

		// Flush the user data from the session.
		$app->setUserState('com_users.reset.token', null);
		$app->setUserState('com_users.reset.user', null);

		return true;
	}

	/**
	 * @since    1.6
	 */
	function processResetConfirm($data)
	{
		// Get the form.
		$form = $this->getResetConfirmForm();

		// Check for an error.
		if ($form instanceof Exception)
		{
			return $form;
		}

		// Filter and validate the form data.
		$data   = $form->filter($data);
		$return = $form->validate($data);

		// Check for an error.
		if ($return instanceof Exception)
		{
			return $return;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				JFactory::getApplication()->enqueueMessage($message, 'error');
			}

			return false;
		}

		// Find the user id for the given token.
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('activation');
		$query->select('id');
		$query->select('block');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('username') . ' = ' . $db->Quote($data['username']));

		// Get the user id.
		$db->setQuery((string) $query);

		try
		{
			$user = $db->loadObject();
		}
		catch (RuntimeException $e)
		{
			return new JException(JText::sprintf('COM_USERS_DATABASE_ERROR', $e->getMessage()), 500);
		}

		// Check for a user.
		if (empty($user))
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_NOT_FOUND'));
		}

		$parts = explode(':', $user->activation);
		$crypt = $parts[0];
		if (!isset($parts[1]))
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_NOT_FOUND'));
		}
		$salt = $parts[1];
		$testcrypt = JUserHelper::getCryptedPassword($data['token'], $salt);

		// Verify the token
		if (!($crypt == $testcrypt))
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_NOT_FOUND'));
		}

		// Make sure the user isn't blocked.
		if ($user->block)
		{
			throw new RuntimeException(JText::_('COM_USERS_USER_BLOCKED'));
		}

		// Push the user data into the session.
		$app = JFactory::getApplication();
		$app->setUserState('com_users.reset.token', $crypt . ':' . $salt);
		$app->setUserState('com_users.reset.user', $user->id);

		return true;
	}

	/**
	 * Method to start the password reset process.
	 *
	 * @since    1.6
	 */
	public function processResetRequest($data)
	{
		$config = JFactory::getConfig();

		// Get the form.
		$form = $this->getForm();

		// Check for an error.
		if ($form instanceof Exception)
		{
			return $form;
		}

		// Filter and validate the form data.
		$data   = $form->filter($data);
		$return = $form->validate($data);

		// Check for an error.
		if ($return instanceof Exception)
		{
			return $return;
		}

		// Check the validation results.
		if ($return === false)
		{
			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				JFactory::getApplication()->enqueueMessage($message, 'error');
			}
			return false;
		}

		// Find the user id for the given email address.
		$db    = $this->getDb();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from($db->quoteName('#__users'));
		$query->where($db->quoteName('email') . ' = ' . $db->Quote($data['email']));

		// Get the user object.
		$db->setQuery($query);

		$userId = $db->loadResult();

		// Check for a user.
		if (empty($userId))
		{
			throw new InvalidArgumentException(JText::_('COM_USERS_INVALID_EMAIL'));
		}

		// Get the user object.
		$user = JUser::getInstance($userId);

		// Make sure the user isn't blocked.
		if ($user->block)
		{
			throw new InvalidArgumentException(JText::_('COM_USERS_USER_BLOCKED'));
		}

		// Make sure the user isn't a Super Admin.
		if ($user->authorise('core.admin'))
		{
			throw new InvalidArgumentException(JText::_('COM_USERS_REMIND_SUPERADMIN_ERROR'));
		}

		// Make sure the user has not exceeded the reset limit
		if (!$this->checkResetLimit($user))
		{
			//$resetLimit = (int) JFactory::getApplication()->getParams()->get('reset_time');
			throw new RuntimeException(JText::plural(
				'COM_USERS_REMIND_LIMIT_ERROR_N_HOURS'
				, (int) JFactory::getApplication()->getParams()->get('reset_time')
			));
		}
		// Set the confirmation token.
		$token = JApplication::getHash(JUserHelper::genRandomPassword());
		$salt = JUserHelper::getSalt('crypt-md5');
		$hashedToken = md5($token . $salt) . ':' . $salt;

		$user->activation = $hashedToken;

		// Save the user to the database.
		if (!$user->save(true))
		{
			throw new RuntimeException(JText::sprintf('COM_USERS_USER_SAVE_FAILED', $user->getError()));
		}

		// Assemble the password reset confirmation link.
		$mode = $config->get('force_ssl', 0) == 2 ? 1 : -1;
		$itemid = UsersHelperRoute::getLoginRoute();
		$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';
		$link = 'index.php?option=com_users&view=reset&layout=confirm' . $itemid;

		// Put together the email template data.
		$data = $user->getProperties();
		$data['fromname'] = $config->get('fromname');
		$data['mailfrom'] = $config->get('mailfrom');
		$data['sitename'] = $config->get('sitename');
		$data['link_text'] = JRoute::_($link, false, $mode);
		$data['link_html'] = JRoute::_($link, true, $mode);
		$data['token'] = $token;

		$subject = JText::sprintf(
			'COM_USERS_EMAIL_PASSWORD_RESET_SUBJECT',
			$data['sitename']
		);

		$body = JText::sprintf(
			'COM_USERS_EMAIL_PASSWORD_RESET_BODY',
			$data['sitename'],
			$data['token'],
			$data['link_text']
		);

		// Send the password reset request email.
		$return = JFactory::getMailer()
			->sendMail($data['mailfrom'], $data['fromname'], $user->email, $subject, $body);

		// Check for an error.
		if ($return !== true)
		{
			throw new RuntimeException(JText::_('COM_USERS_MAIL_FAILED'));
		}

		return true;
	}

	/**
	 * Method to check if user reset limit has been exceeded within the allowed time period.
	 *
	 * @param   JUser $user the user doing the password reset
	 *
	 * @return  boolean true if user can do the reset, false if limit exceeded
	 *
	 * @since    2.5
	 */
	public function checkResetLimit($user)
	{
		$params     = JFactory::getApplication()->getParams();
		$maxCount   = (int) $params->get('reset_count');
		$resetHours = (int) $params->get('reset_time');
		$result     = true;

		$lastResetTime = strtotime($user->lastResetTime) ? strtotime($user->lastResetTime) : 0;
		$hoursSinceLastReset = (strtotime(JFactory::getDate()->toSql()) - $lastResetTime) / 3600;

		if ($hoursSinceLastReset > $resetHours)
		{
			// If it's been long enough, start a new reset count
			$user->lastResetTime = JFactory::getDate()->toSql();
			$user->resetCount = 1;
		}
		elseif ($user->resetCount < $maxCount)
		{
			// If we are under the max count, just increment the counter
			$user->resetCount;
		}
		else
		{
			// At this point, we know we have exceeded the maximum resets for the time period
			$result = false;
		}

		return $result;
	}
}
