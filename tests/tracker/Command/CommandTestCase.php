<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command;

use Joomla\Console\Application;
use Joomla\Console\Command\AbstractCommand;
use Joomla\DI\Container;
use Joomla\Github\Package\Authorization;
use Joomla\Registry\Registry;
use JTracker\Tests\Mocks\RecordingDatabaseMockTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Base class for CLI command tests.
 *
 * Wires a command to a console application, a DI container, a database mock and a
 * GitHub stub, so the command can run without network or database access.
 *
 * @since  1.0
 */
abstract class CommandTestCase extends TestCase
{
    use RecordingDatabaseMockTrait;

    /**
     * The project row returned for the "#__tracker_projects" query.
     *
     * @var    array
     * @since  1.0
     */
    protected $projectRow = [
        'project_id'      => 1,
        'title'           => 'Joomla! CMS',
        'gh_user'         => 'joomla',
        'gh_project'      => 'joomla-cms',
        'gh_editbot_user' => '',
        'gh_editbot_pass' => '',
    ];

    /**
     * Output of the last command run.
     *
     * @var    BufferedOutput
     * @since  1.0
     */
    protected $output;

    /**
     * Sets up the fixture.
     *
     * Table column definitions are cached statically by AbstractDatabaseTable, so all tables
     * used by the commands are registered for every test to keep the tests order independent.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        $this->addDbTableColumns('#__tracker_labels', ['label_id', 'project_id', 'name', 'color']);
        $this->addDbTableColumns(
            '#__tracker_milestones',
            ['milestone_id', 'milestone_number', 'project_id', 'title', 'description', 'state', 'due_on']
        );
        $this->addDbTableColumns(
            '#__activities',
            ['activities_id', 'gh_comment_id', 'issue_number', 'project_id', 'user', 'event', 'text', 'text_raw', 'created_date', 'updated_date']
        );
    }

    /**
     * Create a GitHub stub with a healthy rate limit.
     *
     * The returned object mimics the property chain used by the commands
     * ($github->issues->labels etc.). Assign mocks of the API classes to it.
     *
     * @return  \stdClass
     *
     * @since   1.0
     */
    protected function createGitHubStub(): \stdClass
    {
        $rate = (object) [
            'resources' => (object) [
                'core' => (object) ['limit' => 5000, 'remaining' => 5000],
            ],
        ];

        $authorization = $this->createStub(Authorization::class);
        $authorization->method('getRateLimit')->willReturn($rate);

        $github                = new \stdClass();
        $github->authorization = $authorization;
        $github->issues        = new \stdClass();

        return $github;
    }

    /**
     * Run a command.
     *
     * @param   AbstractCommand  $command     The command.
     * @param   array            $parameters  The input parameters.
     * @param   object           $db          The database mock.
     * @param   object|null      $github      The GitHub stub.
     * @param   array            $config      Application configuration.
     *
     * @return  integer  The exit code
     *
     * @since   1.0
     */
    protected function runCommand(AbstractCommand $command, array $parameters, $db, $github = null, array $config = []): int
    {
        $this->addDbResult('/#__tracker_projects/', [(object) $this->projectRow]);

        $this->output = new BufferedOutput();
        $input        = new ArrayInput($parameters);
        $input->setInteractive(false);

        $application = new Application($input, $this->output, new Registry($config));
        $application->setAutoExit(false);

        $container = new Container();
        $container->set('db', $db);
        $container->set('logger', new NullLogger());

        if ($github !== null) {
            $container->set('gitHub', $github);
        }

        $command->setApplication($application);
        $command->setContainer($container);

        return $command->execute($input, $this->output);
    }

    /**
     * Read a protected or private property.
     *
     * @param   object  $object    The object.
     * @param   string  $property  The property name.
     *
     * @return  mixed
     *
     * @since   1.0
     */
    protected function readProperty(object $object, string $property)
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($object);
    }

    /**
     * Write a protected or private property.
     *
     * @param   object  $object    The object.
     * @param   string  $property  The property name.
     * @param   mixed   $value     The value.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function writeProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);
    }
}
