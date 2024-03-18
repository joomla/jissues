<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Application;

use App\Debug\TrackerDebugger;
use App\Projects\Model\ProjectModel;
use App\Projects\TrackerProject;
use Joomla\Application\AbstractWebApplication;
use Joomla\Application\Controller\ControllerResolverInterface;
use Joomla\Controller\AbstractController;
use Joomla\Controller\ControllerInterface;
use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Router\Router;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Authentication\User;
use JTracker\Controller\TrackerControllerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Joomla Tracker web application class
 *
 * @since  1.0
 */
final class Application extends AbstractWebApplication implements ContainerAwareInterface, DispatcherAwareInterface
{
    use ContainerAwareTrait;
    use DispatcherAwareTrait;

    /**
     * The application's controller resolver.
     *
     * @var    ControllerResolverInterface
     * @since  1.0
     */
    protected $controllerResolver;

    /**
     * The name of the application.
     *
     * @var    array
     * @since  1.0
     */
    protected $name;

    /**
     * A session object.
     *
     * @var    Session
     * @since  1.0
     * @note   This has been created to avoid a conflict with the $session member var from the parent class.
     */
    private $newSession;

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
     * The default project id when all else fails.
     * TODO: Pull this from config?
     *
     * @var    integer
     * @since  __DEPLOY_VERSION__
     */
    private $defaultProjectId = 1;

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
        parent::__construct($input, $config);

        $this->newSession = $session;
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
        $this->mark('Routing request');

        $route = $this->getRouter()->parseRoute($this->get('uri.route'), $this->getInput()->getMethod());

        $this->set('_resolved_route', $route);

        // Add variables to the input if not already set
        foreach ($route->getRouteVariables() as $key => $value) {
            $this->getInput()->def($key, $value);
        }

        $this->mark('Resolving controller');

        $controller = $this->getControllerResolver()->resolve($route);

        if (\is_array($controller) && \is_object($controller[0])) {
            /** @var TrackerControllerInterface|ControllerInterface $controllerInstance */
            $controllerInstance = $controller[0];

            if ($controllerInstance instanceof AbstractController) {
                $controllerInstance->setApplication($this);
                $controllerInstance->setInput($this->getInput());
            }

            if ($controllerInstance instanceof ContainerAwareInterface) {
                $controllerInstance->setContainer($this->getContainer());
            }

            if ($controllerInstance instanceof TrackerControllerInterface) {
                $controllerInstance->initialize();
            }
        }

        $this->mark('Controller initialized.');

        // Execute the controller
        $contents = \call_user_func($controller);
        $this->mark('Controller executed');

        $this->mark('Application executed OK');

        $this->checkRememberMe();

        if (!\is_bool($contents)) {
            $this->setBody($contents);
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
        if ($this->get('debug.system')) {
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
        if (!$this->newSession->isStarted()) {
            $this->newSession->start();

            $registry = $this->newSession->get('registry');

            if ($registry === null) {
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
        if ($id) {
            return new GitHubUser($this->getProject(), $this->getContainer()->get('db'), $id);
        }

        if ($this->user === null) {
            $sessionUser = $this->getSession()->get('jissues_user');

            if ($sessionUser instanceof User && $sessionUser->id != 0) {
                $sessionUser->setDatabase($this->getContainer()->get('db'));
                $sessionUser->getProject()->setDatabase($this->getContainer()->get('db'));

                $this->setUser($sessionUser->loadBy(['id' => $sessionUser->id, 'username' => $sessionUser->username]));
            } else {
                $this->setUser(null);
            }
        }

        return $this->user;
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
        if ($user === null) {
            try {
                $activeProject = $this->getProject();
            } catch (\UnexpectedValueException $e) {
                // If the project can't be found try and fallback - this can happen due to the
                // exception handlers dependency on the timezone (hence user).
                $activeProject = (new ProjectModel($this->getContainer()->get('db')))
                    ->getItem($this->defaultProjectId);
            }

            // Logout
            $this->user = new GitHubUser($activeProject, $this->getContainer()->get('db'));

            $this->getSession()->set('jissues_user', $this->user);

            // @todo cleanup more ?
        } elseif ($user instanceof User) {
            // Login
            $user->isAdmin = \in_array($user->username, $this->get('acl.admin_users'));

            $this->user = $user;

            $this->getSession()->set('jissues_user', $user);
        } else {
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

        if ($registry !== null) {
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
        $new_state = $this->getInput()->get($request, null, $type);

        // Save the new value only if it was set in this request.
        if ($new_state !== null) {
            $this->setUserState($key, $new_state);
        } else {
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

        if ($registry !== null) {
            return $registry->set($key, $value);
        }
    }

    /**
     * Get the current project.
     *
     * @param   boolean  $reload  Reload the project.
     *
     * @return  TrackerProject
     *
     * @since   1.0
     * @throws  \UnexpectedValueException
     */
    public function getProject($reload = false)
    {
        if ($this->project === null || $reload) {
            $alias = $this->getInput()->get('project_alias');

            if ($alias) {
                // Change the project
                $project = (new ProjectModel($this->getContainer()->get('db')))
                    ->getByAlias($alias);
            } else {
                $sessionAlias = $this->getSession()->get('project_alias');

                // No Project set
                if ($sessionAlias) {
                    // Found a session Project.
                    $project = (new ProjectModel($this->getContainer()->get('db')))
                        ->getByAlias($sessionAlias);
                } else {
                    // Nothing found - Get a default project !
                    $project = (new ProjectModel($this->getContainer()->get('db')))
                        ->getItem($this->defaultProjectId);
                }
            }

            $this->getSession()->set('project_alias', $project->alias);
            $this->getInput()->set('project_id', $project->project_id);

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
        if (!$this->get('system.remember_me')) {
            // Remember me is disabled in config
            return $this;
        }

        if ($this->getUser()->id) {
            // The user is already logged in
            return $this;
        }

        if (!$this->getInput()->cookie->get('remember_me')) {
            // No "remember me" cookie found
            return $this;
        }

        /** @var GitHubLoginHelper $loginHelper */
        $loginHelper = $this->getContainer()->get(GitHubLoginHelper::class);

        // Redirect and login with GitHub
        $this->allowCache(false);
        $this->redirect($loginHelper->getLoginUri());

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
        if (!$this->get('system.remember_me')) {
            return $this;
        }

        if ($state) {
            // Remember me - set the cookie
            $value = '1';

            // One year - approx.
            $expire = time() + 3600 * 24 * 365;
        } else {
            // Forget me - delete the cookie
            $value  = '';
            $expire = time() - 3600;
        }

        $this->getInput()->cookie->set('remember_me', $value, $expire);

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
     * Get the router.
     *
     * @return  Router
     *
     * @since   1.0
     * @throws  \UnexpectedValueException May be thrown if the router has not been set.
     */
    public function getRouter()
    {
        if ($this->router) {
            return $this->router;
        }

        throw new \UnexpectedValueException('Router not set in ' . __CLASS__);
    }

    /**
     * Set the application's controller resolver.
     *
     * @param   ControllerResolverInterface  $controllerResolver  Controller resolver to set.
     *
     * @return  $this
     *
     * @since   1.0
     */
    public function setControllerResolver(ControllerResolverInterface $controllerResolver)
    {
        $this->controllerResolver = $controllerResolver;

        return $this;
    }

    /**
     * Get the controller resolver.
     *
     * @return  ControllerResolverInterface
     *
     * @since   1.0
     * @throws  \UnexpectedValueException May be thrown if the controller resolver has not been set.
     */
    public function getControllerResolver()
    {
        if ($this->controllerResolver) {
            return $this->controllerResolver;
        }

        throw new \UnexpectedValueException('Controller resolver not set in ' . __CLASS__);
    }
}
