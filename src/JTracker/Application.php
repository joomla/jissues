<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker;

use App\Debug\TrackerDebugger;
use App\Projects\Model\ProjectModel;
use App\Projects\TrackerProject;

use ElKuKu\G11n\G11n;
use ElKuKu\G11n\Support\ExtensionHelper;

use Joomla\Application\AbstractWebApplication;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Renderer\RendererInterface;
use Joomla\Router\Router;

use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Authentication\User;
use JTracker\Controller\AbstractTrackerController;
use JTracker\Helper\LanguageHelper;
use JTracker\Router\Exception\RoutingException;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Joomla Tracker web application class
 *
 * @since  1.0
 */
final class Application extends AbstractWebApplication implements ContainerAwareInterface, DispatcherAwareInterface
{
	use ContainerAwareTrait, DispatcherAwareTrait, ApplicationTrait;

	/**
	 * The name of the application.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $name = null;

	/**
	 * A session object.
	 *
	 * @var    Session
	 * @since  1.0
	 * @note   This has been created to avoid a conflict with the $session member var from the parent class.
	 */
	private $newSession = null;

	/**
	 * Application router.
	 *
	 * @var    Router
	 * @since  1.0
	 */
	private $router;

	/**
	 * The User object.
	 *
	 * @var    User
	 * @since  1.0
	 */
	private $user;

	/**
	 * The Project object
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	private $project;

	/**
	 * The current language in use. E.g. "en-GB".
	 *
	 * @var    string
	 * @since  1.0
	 */
	private $languageTag = 'en-GB';

	/**
	 * Class constructor.
	 *
	 * @param   Session   $session  The application's session object
	 * @param   Input     $input    The application's input object
	 * @param   Registry  $config   The application's configuration object
	 *
	 * @since   1.0
	 */
	public function __construct(Session $session, Input $input, Registry $config)
	{
		// Run the parent constructor
		parent::__construct($input, $config);

		$this->newSession = $session;
	}

	/**
	 * Get the current language tag. E.g. en-GB.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getLanguageTag()
	{
		return $this->languageTag;
	}

	/**
	 * Get a debugger object.
	 *
	 * @return  TrackerDebugger
	 *
	 * @since   1.0
	 */
	public function getDebugger()
	{
		return $this->getContainer()->get('debugger');
	}

	/**
	 * Method to run the Web application routines.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
		try
		{
			$this->bootApps();

			$this->mark('Apps booted');

			// Fetch the controller
			/** @var AbstractTrackerController $controller */
			$controller = $this->getRouter()->getController($this->get('uri.route'));

			$this->mark('Initializing controller: ' . get_class($controller));
			$controller->initialize();
			$this->mark('Controller initialized.');

			// Load the language for the application
			// @todo language must be loaded after routing is processed cause the Project object is coupled with the User object...
			$this->loadLanguage();

			// Execute the App

			// Define the app path
			define('JPATH_APP', JPATH_ROOT . '/src/App/' . ucfirst($controller->getApp()));
			$this->mark('JPATH_APP=' . JPATH_APP);

			// Load the App language file
			G11n::loadLanguage($controller->getApp(), 'App');
			$this->mark('Language loaded');

			$contents = $controller->execute();
			$this->mark('Controller executed');

			if (!$contents)
			{
				throw new \UnexpectedValueException(sprintf("The %s controller's execute() method did not return anything!", get_class($controller)));
			}

			$this->mark('Application terminated OK');

			$this->checkRememberMe();

			$contents = str_replace('%%%DEBUG%%%', $this->getDebugger()->getOutput(), $contents);

			$this->setBody($contents);
		}
		catch (AuthenticationException $exception)
		{
			$this->setHeader('Status', 403, true);

			$this->mark('Application terminated with an AUTH EXCEPTION');

			$context = [];
			$context['message'] = 'Authentication failure';

			if (JDEBUG)
			{
				// The exceptions contains the User object and the action.
				if ($exception->getUser()->username)
				{
					$context['user'] = $exception->getUser()->username;
					$context['id'] = $exception->getUser()->id;
				}

				$context['action'] = $exception->getAction();
			}

			$this->setBody($this->renderException($exception, $context));
		}
		catch (RoutingException $exception)
		{
			$this->setHeader('Status', 404, true);

			$this->mark('Application terminated with a ROUTING EXCEPTION');

			$context = JDEBUG ? ['message' => $exception->getRawRoute()] : [];

			$this->setBody($this->renderException($exception, $context));
		}
		catch (\Exception $exception)
		{
			// Log the error
			$this->getLogger()->critical(
				sprintf(
					'Exception of type %1$s thrown',
					get_class($exception)
				),
				['exception' => $exception]
			);

			$this->setErrorHeader($exception);

			$this->mark('Application terminated with an EXCEPTION');

			$this->setBody($this->renderException($exception));
		}
	}

	/**
	 * Add a profiler mark.
	 *
	 * @param   string  $text  The message for the mark.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function mark($text)
	{
		if ($this->get('debug.system'))
		{
			$this->getDebugger()->mark($text);
		}

		return $this;
	}

	/**
	 * Enqueue a system message.
	 *
	 * @param   string  $msg   The message to enqueue.
	 * @param   string  $type  The message type. Default is message.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	public function enqueueMessage($msg, $type = 'message')
	{
		$this->getSession()->getFlashBag()->add($type, $msg);

		return $this;
	}

	/**
	 * Get a session object.
	 *
	 * @return  Session
	 *
	 * @since   1.0
	 */
	public function getSession()
	{
		if (!$this->newSession->isStarted())
		{
			$this->newSession->start();

			$registry = $this->newSession->get('registry');

			if (is_null($registry))
			{
				$this->newSession->set('registry', new Registry('session'));
			}
		}

		return $this->newSession;
	}

	/**
	 * Get a user object.
	 *
	 * @param   integer  $id  The user id or the current user.
	 *
	 * @return  User
	 *
	 * @since   1.0
	 */
	public function getUser($id = 0)
	{
		if ($id)
		{
			return new GitHubUser($this->getProject(), $this->getContainer()->get('db'), $id);
		}

		if (is_null($this->user))
		{
			$sessionUser = $this->getSession()->get('jissues_user');

			if ($sessionUser instanceof User && $sessionUser->id != 0)
			{
				$sessionUser->setDatabase($this->getContainer()->get('db'));
				$sessionUser->getProject()->setDatabase($this->getContainer()->get('db'));

				$this->setUser($sessionUser->loadBy(['id' => $sessionUser->id, 'username' => $sessionUser->username]));
			}
			else
			{
				$this->setUser(null);
			}
		}

		return $this->user;
	}

	/**
	 * Get a language object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 */
	protected function loadLanguage()
	{
		$languages = LanguageHelper::getLanguageCodes();

		// Get the language tag from user input.
		$lang = $this->input->get('lang');

		if ($lang)
		{
			if (false === in_array($lang, $languages))
			{
				// Unknown language from user input - fall back to default
				$lang = G11n::getDefault();
			}

			if (false === in_array($lang, $languages))
			{
				// Unknown default language - Fall back to British.
				$lang = 'en-GB';
			}

			// Store the language tag.
			$this->getSession()->set('lang', $lang);
			$this->getUser()->params->set('language', $lang);
		}
		else
		{
			/*
			 * Get the language tag:
			 * 1. From the session
			 * 2. From the user param
			 * 3. Default to British
			 */
			$lang = $this->getSession()->get('lang', $this->getUser()->params->get('language', 'en-GB'));
		}

		if ($lang)
		{
			// Set the current language if anything has been found.
			G11n::setCurrent($lang);

			$this->languageTag = $lang;
		}

		// Set language debugging.
		G11n::setDebug($this->get('debug.language'));

		// Set the language cache directory.
		if ('vagrant' == getenv('JTRACKER_ENVIRONMENT'))
		{
			ExtensionHelper::setCacheDir('/tmp');
		}
		else
		{
			ExtensionHelper::setCacheDir(JPATH_ROOT . '/cache');
		}

		// Load the core language file.
		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		G11n::loadLanguage('JTracker', 'Core');

		// Load template language files.
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		G11n::loadLanguage('JTracker', 'Template');

		// Add the App domain path.
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');

		if ($this->get('debug.system')
			|| $this->get('debug.database')
			|| $this->get('debug.language'))
		{
			// Load the Debug App language file.
			G11n::loadLanguage('Debug', 'App');
		}

		return $this;
	}

	/**
	 * Login or logout a user.
	 *
	 * @param   User  $user  The user object.
	 *
	 * @return  $this  Method allows chaining
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function setUser(User $user = null)
	{
		if (is_null($user))
		{
			// Logout
			$this->user = new GitHubUser($this->getProject(), $this->getContainer()->get('db'));

			$this->getSession()->set('jissues_user', $this->user);

			// @todo cleanup more ?
		}
		elseif ($user instanceof User)
		{
			// Login
			$user->isAdmin = in_array($user->username, $this->get('acl.admin_users'));

			$this->user = $user;

			$this->getSession()->set('jissues_user', $user);
		}
		else
		{
			throw new \UnexpectedValueException('Wrong parameter when instantiating a new user object.');
		}

		return $this;
	}

	/**
	 * Clear the system message queue.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function clearMessageQueue()
	{
		$this->getSession()->getFlashBag()->clear();
	}

	/**
	 * Get the system message queue.
	 *
	 * @return  array  The system message queue.
	 *
	 * @since   1.0
	 */
	public function getMessageQueue()
	{
		return $this->getSession()->getFlashBag()->peekAll();
	}

	/**
	 * Set the system message queue for a given type.
	 *
	 * @param   string  $type     The type of message to set
	 * @param   mixed   $message  Either a single message or an array of messages
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setMessageQueue($type, $message = '')
	{
		$this->getSession()->getFlashBag()->set($type, $message);
	}

	/**
	 * Gets a user state.
	 *
	 * @param   string  $key      The path of the state.
	 * @param   mixed   $default  Optional default value, returned if the internal value is null.
	 *
	 * @return  mixed  The user state or null.
	 *
	 * @since   1.0
	 */
	public function getUserState($key, $default = null)
	{
		/** @var Registry $registry */
		$registry = $this->getSession()->get('registry');

		if (!is_null($registry))
		{
			return $registry->get($key, $default);
		}

		return $default;
	}

	/**
	 * Gets the value of a user state variable.
	 *
	 * @param   string  $key      The key of the user state variable.
	 * @param   string  $request  The name of the variable passed in a request.
	 * @param   string  $default  The default value for the variable if not found. Optional.
	 * @param   string  $type     Filter for the variable, for valid values see {@link JFilterInput::clean()}. Optional.
	 *
	 * @return  mixed  The request user state.
	 *
	 * @since   1.0
	 */
	public function getUserStateFromRequest($key, $request, $default = null, $type = 'none')
	{
		$cur_state = $this->getUserState($key, $default);
		$new_state = $this->input->get($request, null, $type);

		// Save the new value only if it was set in this request.
		if ($new_state !== null)
		{
			$this->setUserState($key, $new_state);
		}
		else
		{
			$new_state = $cur_state;
		}

		return $new_state;
	}

	/**
	 * Sets the value of a user state variable.
	 *
	 * @param   string  $key    The path of the state.
	 * @param   string  $value  The value of the variable.
	 *
	 * @return  mixed  The previous state, if one existed.
	 *
	 * @since   1.0
	 */
	public function setUserState($key, $value)
	{
		/** @var Registry $registry */
		$registry = $this->getSession()->get('registry');

		if (!is_null($registry))
		{
			return $registry->set($key, $value);
		}

		return null;
	}

	/**
	 * Get the current project.
	 *
	 * @param   boolean  $reload  Reload the project.
	 *
	 * @return  TrackerProject
	 *
	 * @since   1.0
	 * @throws  \InvalidArgumentException
	 */
	public function getProject($reload = false)
	{
		if (is_null($this->project) || $reload)
		{
			$alias = $this->input->get('project_alias');

			if ($alias)
			{
				// Change the project
				$project = (new ProjectModel($this->getContainer()->get('db')))
					->getByAlias($alias);

				if (!$project)
				{
					// No project...
					throw new \InvalidArgumentException('Invalid project');
				}
			}
			else
			{
				$sessionAlias = $this->getSession()->get('project_alias');

				// No Project set
				if ($sessionAlias)
				{
					// Found a session Project.
					$project = (new ProjectModel($this->getContainer()->get('db')))
						->getByAlias($sessionAlias);
				}
				else
				{
					// Nothing found - Get a default project !
					$project = (new ProjectModel($this->getContainer()->get('db')))
						->getItem(1);
				}
			}

			$this->getSession()->set('project_alias', $project->alias);
			$this->input->set('project_id', $project->project_id);

			$this->project = $project;

			// Set the changed project to the user object
			$this->getUser()->setProject($project);
		}

		return $this->project;
	}

	/**
	 * Check the "remember me" cookie and try to login with GitHub.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	private function checkRememberMe()
	{
		if (!$this->get('system.remember_me'))
		{
			// Remember me is disabled in config
			return $this;
		}

		if ($this->getUser()->id)
		{
			// The user is already logged in
			return $this;
		}

		if (!$this->input->cookie->get('remember_me'))
		{
			// No "remember me" cookie found
			return $this;
		}

		// Redirect and login with GitHub
		$this->allowCache(false);
		$this->redirect((new GitHubLoginHelper($this->getContainer()))->getLoginUri());

		return $this;
	}

	/**
	 * Set a "remember me" cookie.
	 *
	 * @param   boolean  $state  Remember me or forget me.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setRememberMe($state)
	{
		if (!$this->get('system.remember_me'))
		{
			return $this;
		}

		if ($state)
		{
			// Remember me - set the cookie
			$value = '1';

			// One year - approx.
			$expire = time() + 3600 * 24 * 365;
		}
		else
		{
			// Forget me - delete the cookie
			$value = '';
			$expire = time() - 3600;
		}

		$this->input->cookie->set('remember_me', $value, $expire);

		return $this;
	}

	/**
	 * Set the application's router.
	 *
	 * @param   Router  $router  Router object to set.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function setRouter(Router $router)
	{
		$this->router = $router;

		return $this;
	}

	/**
	 * Get the event dispatcher.
	 *
	 * @return  Router
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException May be thrown if the router has not been set.
	 */
	public function getRouter()
	{
		if ($this->router)
		{
			return $this->router;
		}

		throw new \UnexpectedValueException('Router not set in ' . __CLASS__);
	}

	/**
	 * Set the HTTP Response Header for error conditions
	 *
	 * @param   \Exception  $exception  The Exception object
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	private function setErrorHeader(\Exception $exception)
	{
		switch ($exception->getCode())
		{
			case 401 :
				$this->setHeader('HTTP/1.1 401 Unauthorized', 401, true);

				break;

			case 403 :
				$this->setHeader('HTTP/1.1 403 Forbidden', 403, true);

				break;

			case 404 :
				$this->setHeader('HTTP/1.1 404 Not Found', 404, true);

				break;

			case 405 :
				$this->setHeader('HTTP/1.1 405 Method Not Allowed', 405, true);

				break;

			case 500 :
			default  :
				$this->setHeader('HTTP/1.1 500 Internal Server Error', 500, true);

				break;
		}
	}

	/**
	 * Method to render an exception in a user friendly format
	 *
	 * @param   \Exception  $exception  The caught exception.
	 * @param   array       $context    The message to display.
	 *
	 * @return  string  The exception output in rendered format.
	 *
	 * @since   1.0
	 */
	public function renderException(\Exception $exception, array $context = [])
	{
		static $loaded = false;

		// Attach the Exception to the context array (per PSR-3) if not already
		if (!isset($context['exception']))
		{
			$context['exception'] = $exception;
		}

		if ($loaded)
		{
			// Seems that we're recursing...
			$this->getLogger()->error($exception->getCode() . ' ' . $exception->getMessage(), $context);

			return str_replace(JPATH_ROOT, 'JROOT', $exception->getMessage())
			. '<pre>' . $exception->getTraceAsString() . '</pre>'
			. 'Previous: ' . get_class($exception->getPrevious());
		}

		$rendererName = $this->get('renderer.type');

		// The renderer should exist in the container
		if (!$this->getContainer()->exists("renderer.$rendererName"))
		{
			throw new \RuntimeException('Unsupported renderer: ' . $rendererName);
		}

		/** @var RendererInterface $renderer */
		$renderer = $this->getContainer()->get("renderer.$rendererName");

		// Alias the renderer to the interface if not set already
		if (!$this->getContainer()->exists(RendererInterface::class))
		{
			$this->getContainer()->alias(RendererInterface::class, "renderer.$rendererName");
		}

		$message = '';

		foreach ($context as $key => $value)
		{
			// Only render the Exception if debugging
			if ($key === 'exception' && !JDEBUG)
			{
				continue;
			}

			$message .= $key . ': ' . $value . "\n";
		}

		$renderer->set('exception', $exception)
			->set('message', str_replace(JPATH_ROOT, 'ROOT', $message))
			->set('view', 'exception')
			->set('layout', '')
			->set('app', 'core');

		$loaded = true;

		$contents = $renderer->render('exception.twig');

		$debug = JDEBUG ? $this->getDebugger()->getOutput() : '';

		$contents = str_replace('%%%DEBUG%%%', $debug, $contents);

		$this->getLogger()->error($exception->getCode() . ' ' . $exception->getMessage(), $context);

		return $contents;
	}
}
