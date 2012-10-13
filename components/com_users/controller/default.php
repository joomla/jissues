<?php

class UsersControllerDefault extends JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 * @throws  RuntimeException if view class not found
	 */
	public function execute()
	{
		// Get the application
		/* @var JApplicationWeb $app */
		$app   = $this->getApplication();
		$input = $this->getInput();

		// Get the document object.
		$document = $app->getDocument();

		$vName   = $input->getWord('view', 'login');
		$vFormat = $document->getType();
		$lName   = $input->getWord('layout', 'default');

		$input->set('view', $vName);

		// Register the layout paths for the view
		$paths = new SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/view/' . $vName . '/tmpl', 'normal');

		$vClass = 'UsersView' . ucfirst($vName) . ucfirst($vFormat);
		$mClass = 'UsersModel' . ucfirst($vName);

		if (!class_exists($vClass))
		{
			throw new RuntimeException('View not found: ' . $vName);
		}

		/* @var JViewHtml $view */
		$view = new $vClass(new $mClass, $paths);
		$view->setLayout($lName);

		// Render our view.
		echo $view->render();

		return true;
	}

	/**
	 * Method to display a view.
	 *
	 * @param    boolean            If true, the view output will be cached
	 * @param    array              An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return    JController        This object to support chaining.
	 * @since    1.5
	 */
	public function THIS_IS_OLD_CODE()
	{
		// Get the document object.
		$document = JFactory::getDocument();

		// Set the default view name and format from the Request.
		$vName = $this->input->getCmd('view', 'login');
		$vFormat = $document->getType();
		$lName = $this->input->getCmd('layout', 'default');

		if ($view = $this->getView($vName, $vFormat))
		{
			// Do any specific processing by view.
			switch ($vName)
			{
				case 'registration':
					// If the user is already logged in, redirect to the profile page.
					$user = JFactory::getUser();
					if ($user->get('guest') != 1)
					{
						// Redirect to profile page.
						$this->setRedirect(JRoute::_('index.php?option=com_users&view=profile', false));
						return;
					}

					// Check if user registration is enabled
					if (0) // JComponentHelper::getParams('com_users')->get('allowUserRegistration') == 0)
					{
						// Registration is disabled - Redirect to login page.
						$this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));
						return;
					}

					// The user is a guest, load the registration model and show the registration page.
					$model = $this->getModel('Registration');
					break;

				// Handle view specific models.
				case 'profile':

					// If the user is a guest, redirect to the login page.
					$user = JFactory::getUser();
					if ($user->get('guest') == 1)
					{
						// Redirect to login page.
						$this->setRedirect(JRoute::_('index.php?option=com_users&view=login', false));
						return;
					}
					$model = $this->getModel($vName);
					break;

				// Handle the default views.
				case 'login':
					$model = $this->getModel($vName);
					break;

				case 'reset':
					// If the user is already logged in, redirect to the profile page.
					$user = JFactory::getUser();
					if ($user->get('guest') != 1)
					{
						// Redirect to profile page.
						$this->setRedirect(JRoute::_('index.php?option=com_users&view=profile', false));
						return;
					}

					$model = $this->getModel($vName);
					break;

				case 'remind':
					// If the user is already logged in, redirect to the profile page.
					$user = JFactory::getUser();
					if ($user->get('guest') != 1)
					{
						// Redirect to profile page.
						$this->setRedirect(JRoute::_('index.php?option=com_users&view=profile', false));
						return;
					}

					$model = $this->getModel($vName);
					break;

				default:
					$model = $this->getModel('Login');
					break;
			}

			// Push the model into the view (as default).
			$view->setModel($model, true);
			$view->setLayout($lName);

			// Push document object into the view.
			$view->document = $document;

			$view->display();
		}
	}

}
