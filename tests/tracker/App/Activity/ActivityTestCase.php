<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Activity;

use App\Projects\TrackerProject;
use Joomla\DI\Container;
use Joomla\Input\Input;
use JTracker\Controller\AbstractAjaxController;
use JTracker\Tests\Mocks\RecordingDatabaseMockTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Base class for the activity chart tests.
 *
 * The web application class is final, so a minimal stand-in exposing the members used by
 * the AJAX controllers (input, getProject(), getLogger(), mimeType) is put into the container.
 *
 * @since  1.0
 */
abstract class ActivityTestCase extends TestCase
{
    use RecordingDatabaseMockTrait;

    /**
     * Create a project.
     *
     * @param   object  $db  The database mock.
     *
     * @return  TrackerProject
     *
     * @since   1.0
     */
    protected function createProject($db): TrackerProject
    {
        return new TrackerProject($db, ['project_id' => 3, 'title' => 'Joomla! CMS']);
    }

    /**
     * Create the application stand-in.
     *
     * @param   array           $request  The request variables.
     * @param   TrackerProject  $project  The active project.
     *
     * @return  object
     *
     * @since   1.0
     */
    protected function createApplication(array $request, TrackerProject $project): object
    {
        return new class (new Input($request), $project) {
            /**
             * @var  Input
             */
            public $input;

            /**
             * @var  string
             */
            public $mimeType = 'text/html';

            /**
             * @var  TrackerProject
             */
            private $project;

            /**
             * Constructor.
             *
             * @param   Input           $input    The input.
             * @param   TrackerProject  $project  The project.
             */
            public function __construct(Input $input, TrackerProject $project)
            {
                $this->input   = $input;
                $this->project = $project;
            }

            /**
             * Get the active project.
             *
             * @return  TrackerProject
             */
            public function getProject(): TrackerProject
            {
                return $this->project;
            }

            /**
             * Get the logger.
             *
             * @return  NullLogger
             */
            public function getLogger(): NullLogger
            {
                return new NullLogger();
            }
        };
    }

    /**
     * Create and initialise a controller.
     *
     * @param   string  $class    The controller class.
     * @param   array   $request  The request variables.
     *
     * @return  AbstractAjaxController
     *
     * @since   1.0
     */
    protected function createController(string $class, array $request): AbstractAjaxController
    {
        $db = $this->createDatabaseMock();

        $container = new Container();
        $container->set('db', $db);
        $container->set('app', $this->createApplication($request, $this->createProject($db)));

        /** @var AbstractAjaxController $controller */
        $controller = new $class();
        $controller->setContainer($container);
        $controller->initialize();

        return $controller;
    }

    /**
     * Get the state of the controller's model.
     *
     * @param   AbstractAjaxController  $controller  The controller.
     *
     * @return  \Joomla\Registry\Registry
     *
     * @since   1.0
     */
    protected function getModelState(AbstractAjaxController $controller)
    {
        $reflection = new \ReflectionProperty($controller, 'model');
        $reflection->setAccessible(true);

        return $reflection->getValue($controller)->getState();
    }

    /**
     * Execute the controller and decode the JSON response.
     *
     * @param   AbstractAjaxController  $controller  The controller.
     *
     * @return  object
     *
     * @since   1.0
     */
    protected function executeController(AbstractAjaxController $controller): object
    {
        return json_decode($controller->execute());
    }
}
