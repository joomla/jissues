<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\Event;
use Joomla\Input\Input;
use Joomla\Renderer\AddTemplateFolderInterface;
use Joomla\Renderer\RendererInterface;
use JTracker\Github\Github;
use JTracker\Github\GithubFactory;
use JTracker\View\AbstractTrackerHtmlView;

/**
 * Abstract Controller class for the Tracker Application
 *
 * @since  1.0
 */
abstract class AbstractTrackerController implements TrackerControllerInterface, ContainerAwareInterface, DispatcherAwareInterface
{
    use ContainerAwareTrait;
    use DispatcherAwareTrait;

    /**
     * The default view for the app
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultView = '';

    /**
     * The default layout for the app
     *
     * @var    string
     * @since  1.0
     */
    protected $defaultLayout = 'index';

    /**
     * The app being executed.
     *
     * @var    string
     * @since  1.0
     */
    protected $app;

    /**
     * View object
     *
     * @var    \JTracker\View\BaseHtmlView;
     * @since  1.0
     */
    protected $view;

    /**
     * Model object
     *
     * @var    \Joomla\Model\StatefulModelInterface
     * @since  1.0
     */
    protected $model;

    /**
     * Flag if the event listener is set for a hook
     *
     * @var    boolean
     * @since  1.0
     */
    protected $listenerSet = false;

    /**
     * The GitHub object.
     *
     * @var GitHub
     * @since  1.0
     */
    protected $github;

    /**
     * Constructor.
     *
     * @since   1.0
     */
    public function __construct()
    {
        // Detect the App name
        if (empty($this->app)) {
            // Get the fully qualified class name for the current object
            $fqcn = (\get_class($this));

            // Strip the base app namespace off
            $className = str_replace('App\\', '', $fqcn);

            // Explode the remaining name into an array
            $classArray = explode('\\', $className);

            // Set the app as the first object in this array
            $this->app = $classArray[0];
        }
    }

    /**
     * Initialize the controller.
     *
     * This will set up default model and view classes.
     *
     * @return  $this  Method allows chiaining
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function initialize()
    {
        // Get the input
        /** @var Input $input */
        $input = $this->getContainer()->get('app')->input;

        // Get some data from the request
        $viewName   = $input->getWord('view', $this->defaultView);
        $viewFormat = $input->getWord('format', 'html');
        $layoutName = $input->getCmd('layout', $this->defaultLayout);

        if (!$viewName) {
            $parts    = explode('\\', \get_class($this));
            $viewName = strtolower($parts[\count($parts) - 1]);
        }

        $base = '\\App\\' . $this->app;

        $viewClass  = $base . '\\View\\' . ucfirst($viewName) . '\\' . ucfirst($viewName) . ucfirst($viewFormat) . 'View';
        $modelClass = $base . '\\Model\\' . ucfirst($viewName) . 'Model';

        // If a model doesn't exist for our view, revert to the default model
        if (!class_exists($modelClass)) {
            $modelClass = $base . '\\Model\\DefaultModel';

            // If there still isn't a class, panic.
            if (!class_exists($modelClass)) {
                throw new \RuntimeException(
                    \sprintf(
                        'No model found for view %s or a default model for %s',
                        $viewName,
                        $this->app
                    )
                );
            }
        }

        // If there isn't a specific view class for this view, see if the app has a default for this format
        if (!class_exists($viewClass)) {
            $viewClass = $base . '\\View\\Default' . ucfirst($viewFormat) . 'View';

            // Make sure the view class exists, otherwise revert to the default
            if (!class_exists($viewClass)) {
                $viewClass = '\\JTracker\\View\\TrackerDefaultView';
            }
        }

        $this->model = new $modelClass($this->getContainer()->get('db'));

        // Create the view
        /** @var AbstractTrackerHtmlView $view */
        $this->view = new $viewClass(
            $this->model,
            $this->fetchRenderer($viewName, $layoutName)
        );

        $this->view->setLayout($viewName . '.' . $layoutName . '.twig');

        $this->getContainer()->get('app')->mark('Model: ' . $modelClass);
        $this->getContainer()->get('app')->mark('View: ' . $viewClass);
        $this->getContainer()->get('app')->mark('Layout: ' . $layoutName);

        return $this;
    }

    /**
     * Execute the controller.
     *
     * This is a generic method to execute and render a view and is not suitable for tasks.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function execute()
    {
        // Render our view.
        $contents = $this->view->render();

        $this->getContainer()->get('app')->mark('View rendered: ' . $this->view->getLayout());

        return $contents;
    }

    /**
     * Returns the current app
     *
     * @return  string  The app being executed.
     *
     * @since   1.0
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Get a renderer object.
     *
     * @param   string  $view    The view to render
     * @param   string  $layout  The layout in the view
     *
     * @return  RendererInterface
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    protected function fetchRenderer($view, $layout)
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        $rendererName = $application->get('renderer.type');

        // The renderer should exist in the container
        if (!$this->getContainer()->exists("renderer.$rendererName")) {
            throw new \RuntimeException('Unsupported renderer: ' . $rendererName);
        }

        /** @var RendererInterface $renderer */
        $renderer = $this->getContainer()->get("renderer.$rendererName");

        // Add the app path if it exists
        $path = JPATH_TEMPLATES . '/' . strtolower($this->app);

        if ($renderer instanceof AddTemplateFolderInterface && is_dir($path)) {
            $renderer->addFolder($path);
        }

        $renderer
            ->set('view', $view)
            ->set('layout', $layout)
            ->set('app', strtolower($this->getApp()));

        return $renderer;
    }

    /**
     * Registers the event listener for the current project.
     *
     * @param   string  $type  The event listener type.
     *
     * @return  $this
     *
     * @since   1.0
     */
    protected function addEventListener($type)
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('JTracker\\Application\\Application');

        /*
         * Add the event listener if it exists.  Listeners are named in the format of <project><type>Listener in the Hooks\Listeners namespace.
         * For example, the listener for a joomla-cms pull activity would be JoomlacmsPullsListener
         */
        $baseClass = ucfirst(str_replace('-', '', $application->getProject()->gh_project)) . ucfirst($type) . 'Listener';
        $fullClass = 'App\\Tracker\\Controller\\Hooks\\Listeners\\' . $baseClass;

        if (class_exists($fullClass)) {
            $listener = new $fullClass();
            $listener->setContainer($this->getContainer());
            $this->dispatcher->addSubscriber($listener);
            $this->listenerSet = true;
        }

        return $this;
    }

    /**
     * Triggers an event if a listener is set.
     *
     * @param   string  $eventName  Name of the event to trigger.
     * @param   array   $arguments  Associative array of arguments for the event.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function triggerEvent($eventName, array $arguments)
    {
        if (!$this->listenerSet) {
            return;
        }

        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');

        // Create the event with default arguments.
        $event = (new Event($eventName))
            ->addArgument('github', ($this->github ?: GithubFactory::getInstance($application)))
            ->addArgument('project', $application->getProject());

        // Add event arguments passed as parameters.
        foreach ($arguments as $name => $value) {
            $event->addArgument($name, $value);
        }

        // Add the logger if the event doesn't already have it
        if (!$event->hasArgument('logger')) {
            $event->addArgument('logger', $application->getLogger());
        }

        // Trigger the event.
        $this->getDispatcher()->dispatch($eventName, $event);
    }
}
