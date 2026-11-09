<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller;

use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use JTracker\Application\Application;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\Controller\AbstractAjaxController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Helper for testing the AJAX controllers of the Tracker app without a web server.
 *
 * The tests use the real application, so the project is resolved the same way as in a request:
 * from the "project_alias" route variable, falling back to the project stored in the session.
 *
 * @since  1.0
 */
trait AjaxControllerTestTrait
{
    /**
     * The projects known to the database, keyed by alias.
     *
     * @var  array
     */
    private static $projects = [
        'joomla-cms'    => ['project_id' => 42, 'alias' => 'joomla-cms'],
        'other-project' => ['project_id' => 99, 'alias' => 'other-project'],
    ];

    /**
     * Queries passed to setQuery() as strings, without the project lookups.
     *
     * @var  string[]
     */
    protected $issueQueries = [];

    /**
     * Calls to the user which are relevant for the permission checks, in order.
     *
     * @var  string[]
     */
    protected $userCalls = [];

    /**
     * Creates a database driver stub.
     *
     * @param   mixed        $result  The value returned by loadResult()
     * @param   object|null  $object  The object returned by loadObject() for queries which are no project lookups
     *
     * @return  DatabaseDriver
     */
    protected function createDatabase($result = null, ?object $object = null): DatabaseDriver
    {
        $this->issueQueries = [];

        $db        = $this->createStub(DatabaseDriver::class);
        $lastQuery = '';

        $db->method('getQuery')->willReturnCallback(static function () use ($db) {
            return new MysqliQuery($db);
        });

        $db->method('quoteName')->willReturnCallback(static function ($name, $as = null) {
            return '`' . str_replace('.', '`.`', $name) . '`' . ($as === null ? '' : ' AS `' . $as . '`');
        });

        $db->method('quote')->willReturnCallback(static function ($text) {
            return "'" . addslashes($text) . "'";
        });

        $db->method('setQuery')->willReturnCallback(function ($query) use ($db, &$lastQuery) {
            $lastQuery = (string) $query;

            if (!str_contains($lastQuery, '#__tracker_projects')) {
                $this->issueQueries[] = $lastQuery;
            }

            return $db;
        });

        $db->method('loadResult')->willReturn($result);

        $db->method('loadObject')->willReturnCallback(static function () use (&$lastQuery, $object) {
            if (str_contains($lastQuery, '#__tracker_projects')) {
                foreach (self::$projects as $alias => $project) {
                    if (str_contains($lastQuery, "'" . $alias . "'")) {
                        return (object) $project;
                    }
                }

                return null;
            }

            return $object === null ? null : clone $object;
        });

        return $db;
    }

    /**
     * Executes an AJAX controller like a request to it would.
     *
     * The session contains another project than the request, as it happens when the user opened another project in a second tab.
     *
     * @param   AbstractAjaxController  $controller  The controller
     * @param   DatabaseDriver          $db          The database driver
     * @param   array                   $request     The request data, including the route variables
     *
     * @return  object  The decoded response
     */
    protected function executeController(AbstractAjaxController $controller, DatabaseDriver $db, array $request): object
    {
        $this->userCalls = [];

        $session = new Session(new MockArraySessionStorage());
        $session->set('project_alias', 'other-project');

        $app = new Application($session, new Input($request), new Registry(['acl' => ['admin_users' => []]]));

        $container = new Container();
        $container->set('app', $app);
        $container->set('db', $db);

        $app->setContainer($container);

        $user           = $this->createStub(GitHubUser::class);
        $user->id       = 7;
        $user->username = 'tester';

        $user->method('setProject')->willReturnCallback(function ($project) use ($user) {
            $this->userCalls[] = 'setProject:' . $project->alias;

            return $user;
        });

        $user->method('authorize')->willReturnCallback(function ($action) use ($user) {
            $this->userCalls[] = 'authorize:' . $action;

            return $user;
        });

        $user->method('check')->willReturnCallback(function ($action) {
            $this->userCalls[] = 'check:' . $action;

            return true;
        });

        $app->setUser($user);

        $controller->setContainer($container);

        return json_decode($controller->execute());
    }
}
