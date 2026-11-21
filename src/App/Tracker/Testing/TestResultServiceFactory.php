<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

use App\Projects\TrackerProject;
use Joomla\DI\Container;
use JTracker\Github\Github;
use Psr\Log\LoggerInterface;

/**
 * Creates the TestResultService.
 *
 * @since  1.0
 */
class TestResultServiceFactory
{
    /**
     * Create the service for a project.
     *
     * @param   Container             $container  The DI container.
     * @param   TrackerProject        $project    The project.
     * @param   Github                $botGithub  The GitHub object authenticated as the editbot of the project.
     * @param   LoggerInterface|null  $logger     The logger.
     *
     * @return  TestResultService
     *
     * @since   1.0
     */
    public static function create(Container $container, TrackerProject $project, Github $botGithub, ?LoggerInterface $logger = null): TestResultService
    {
        $environment = $container->has(TestEnvironmentProviderInterface::class)
            ? $container->get(TestEnvironmentProviderInterface::class)
            : new NullTestEnvironmentProvider();

        return new TestResultService(
            $project,
            new TestResultRepository($container->get('db')),
            new GithubTestingGateway($botGithub, $project),
            new PrDescriptionSection(),
            $environment,
            $project->getGh_Editbot_User() ?: 'joomla-bot',
            'https://issues.joomla.org',
            $logger
        );
    }
}
