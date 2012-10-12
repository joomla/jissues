<?php
/**
 * User: elkuku
 * Date: 09.10.12
 * Time: 20:32
 */

abstract class JModelTrackerform extends JModelDatabase
{
	/**
	 * Array of form objects.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $forms = array();

	/**
	 * Instantiate the model.
	 *
	 * @since  1.0
	 */
	public function __construct()
	{
		parent::__construct();

		// Populate the state
		$this->loadState();
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   string    $name     The name of the form.
	 * @param   string    $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array     $options  Optional array of options for the form creation.
	 * @param   boolean   $clear    Optional argument to force load a new form.
	 * @param bool|string $xpath    An optional xpath to search for the fields.
	 *
	 * @return  mixed  JForm object on success, False on error.
	 *
	 * @see     JForm
	 * @since   1.0
	 */
	protected function loadForm($name, $source = null, $options = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		$options['control'] = JArrayHelper::getValue($options, 'control', false);

		// Create a signature hash.
		$hash = md5($source . serialize($options));

		// Check if we can use a previously loaded form.
		if (isset($this->forms[$hash]) && !$clear)
		{
			return $this->forms[$hash];
		}

		// Get the form.
		JForm::addFormPath(JPATH_COMPONENT . '/model/forms');
		JForm::addFieldPath(JPATH_COMPONENT . '/model/fields');

		$form = JForm::getInstance($name, $source, $options, false, $xpath);

		if (isset($options['load_data']) && $options['load_data'])
		{
			// Get the data for the form.
			$data = $this->loadFormData();
		}
		else
		{
			$data = array();
		}

		// Allow for additional modification of the form, and events to be triggered.
		// We pass the data because plugins may require it.
		$this->preprocessForm($form, $data);

		// Load the data into the form after the plugins have operated.
		$form->bind($data);

		// Store the form for later.
		$this->forms[$hash] = $form;

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  array  The default data is an empty array.
	 *
	 * @since   1.0
	 */
	protected function loadFormData()
	{
		return array();
	}

	/**
	 * Method to allow derived classes to preprocess the form.
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
	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		// Import the appropriate plugin group.
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

			if (!($error instanceof Exception))
			{
				throw new Exception($error);
			}
		}
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm   $form   The form to validate against.
	 * @param   array   $data   The data to validate.
	 * @param   string  $group  The name of the field group to validate.
	 *
	 * @return  mixed  Array of filtered data if valid, false otherwise.
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 * @since   1.0
	 */
	public function validate(JForm $form, $data, $group = null)
	{
		// Filter and validate the form data.
		$data   = $form->filter($data);
		$result = $form->validate($data, $group);

		// Check for an error.
		if ($result instanceof Exception)
		{
			JFactory::getApplication()->enqueueMessage($result->getMessage());
			return false;
		}

		// Check the validation results.
		if ($result === false)
		{
			$application = JFactory::getApplication();

			// Get the validation messages from the form.
			foreach ($form->getErrors() as $message)
			{
				$application->enqueueMessage($message, 'warning');
			}

			return false;
		}

		return $data;
	}
}
