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
 * @since       1.6
 */
class UsersModelLogin extends JModelTrackerform
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

		$params = JFactory::getApplication()->getParams('com_users');

		// Load the parameters.
		$this->state->set('com_users.params', $params);

		//parent::__construct($state);
	}

	/**
	 * Method to get the login form.
	 *
	 * The base form is loaded from XML and then an event is fired
	 * for users plugins to extend the form with extra fields.
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
		$form = $this->loadForm('com_users.login', 'login', array('load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return    array    The default data is an empty array.
	 * @since    1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered login form data.
		$app = JFactory::getApplication();
		$data = $app->getUserState('users.login.form.data', array());

		// check for return URL from the request first
		if ($return = $app->input->get('return', base64_encode('index.php'), 'base64'))
		{
			$data['return'] = base64_decode($return);
			if (!JURI::isInternal($data['return']))
			{
				$data['return'] = '';
			}
		}

		// Set the return URL if empty.
		if (!isset($data['return']) || empty($data['return']))
		{
			$data['return'] = 'index.php?option=com_users&view=profile';
		}

		$app->setUserState('users.login.form.data', $data);

		return $data;
	}

	/**
	 * Method to allow derived classes to preprocess the form.
	 *
	 * @param JForm  $form
	 * @param mixed  $data
	 * @param string $group The name of the plugin group to import (defaults to "content").
	 *
	 * @throws Exception
	 * @return void
	 * @internal param \A $object form object.
	 * @internal param \The $mixed data expected for the form.
	 * @since    1.6
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'user')
	{
		// Import the approriate plugin group.
		JPluginHelper::importPlugin($group);

		// Get the dispatcher.
		$dispatcher = JEventDispatcher::getInstance();

		// Trigger the form preparation event.
		$results = $dispatcher->trigger('onContentPrepareForm', array($form, $data));

		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true))
		{
			// Get the last error.
			$error = $dispatcher->getError();

			// Convert to a JException if necessary.
			if (!($error instanceof Exception))
			{
				throw new Exception($error);
			}
		}
	}
}
