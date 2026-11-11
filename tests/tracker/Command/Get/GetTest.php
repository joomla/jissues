<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Get;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Registry\Registry;
use JTracker\Command\Get\Get;
use JTracker\Github\Github;
use JTracker\Tests\Command\CommandTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Tests for the GitHub rate limit handling of \JTracker\Command\Get\Get.
 *
 * @since  1.0
 */
class GetTest extends CommandTestCase
{
    /**
     * The users whose rate limit was requested.
     *
     * @var    string[]
     * @since  1.0
     */
    private $requestedUsers = [];

    /**
     * Create a GitHub object whose rate limit endpoint answers per account.
     *
     * @param   array  $remaining  The remaining requests per user name, null for rejected credentials.
     *
     * @return  Github
     *
     * @since   1.0
     */
    private function createGitHub(array $remaining): Github
    {
        $accounts = [];

        foreach (array_keys($remaining) as $username) {
            $accounts[] = (object) ['username' => $username, 'password' => 'secret'];
        }

        $options = new Registry();
        $options->set('api.url', 'https://api.github.com');
        $options->set('api.username', $accounts[0]->username);
        $options->set('api.password', 'secret');
        $options->set('api.accounts', $accounts);

        $http = $this->createStub(Http::class);
        $http->method('get')->willReturnCallback(
            function ($url) use ($remaining) {
                $user = (string) parse_url($url, \PHP_URL_USER);

                $this->requestedUsers[] = $user;

                if ($remaining[$user] === null) {
                    $response = new Response('php://memory', 401);
                    $response->getBody()->write('{"message":"Bad credentials"}');

                    return $response;
                }

                $response = new Response('php://memory', 200);
                $response->getBody()->write(
                    json_encode(['resources' => ['core' => ['limit' => 5000, 'remaining' => $remaining[$user]]]])
                );

                return $response;
            }
        );

        return new Github($options, $http);
    }

    /**
     * Create a command which only sets up the GitHub object.
     *
     * @return  Get
     *
     * @since   1.0
     */
    private function createCommand(): Get
    {
        return new class () extends Get {
            /**
             * Execute the command.
             *
             * @param   InputInterface   $input   The input to inject into the command.
             * @param   OutputInterface  $output  The output to inject into the command.
             *
             * @return  integer
             *
             * @since   1.0
             */
            protected function doExecute(InputInterface $input, OutputInterface $output): int
            {
                $this->setupGitHub();

                return 0;
            }
        };
    }

    /**
     * Run the command and return the GitHub object.
     *
     * @param   array  $remaining  The remaining requests per user name.
     *
     * @return  Github
     *
     * @since   1.0
     */
    private function setupGitHub(array $remaining): Github
    {
        $github = $this->createGitHub($remaining);

        $this->runCommand($this->createCommand(), [], $this->createDatabaseMock(), $github);

        return $github;
    }

    /**
     * The first account is kept if it has enough requests left.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testKeepsFirstAccountWithEnoughRequests(): void
    {
        $github = $this->setupGitHub(['first-bot' => 4000, 'second-bot' => 5000]);

        $this->assertSame('first-bot', $github->getOption('api.username'));
        $this->assertSame(['first-bot'], $this->requestedUsers);
    }

    /**
     * An exhausted first account is replaced by the next account with enough requests.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSwitchesToNextAccountWithEnoughRequests(): void
    {
        $github = $this->setupGitHub(['first-bot' => 0, 'second-bot' => 5000, 'third-bot' => 5000]);

        $this->assertSame('second-bot', $github->getOption('api.username'));
        $this->assertSame(['first-bot', 'second-bot'], $this->requestedUsers);
    }

    /**
     * Accounts with rejected credentials are skipped.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSkipsAccountsWithRejectedCredentials(): void
    {
        $github = $this->setupGitHub(['first-bot' => 0, 'second-bot' => null, 'third-bot' => 4000]);

        $this->assertSame('third-bot', $github->getOption('api.username'));
    }

    /**
     * If all accounts run low, the one with the most requests left is used.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUsesAccountWithMostRequestsIfAllRunLow(): void
    {
        $github = $this->setupGitHub(['first-bot' => 100, 'second-bot' => 300, 'third-bot' => 0]);

        $this->assertSame('second-bot', $github->getOption('api.username'));
    }

    /**
     * The command fails if all accounts are exhausted, and the message lists the limit of each account.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testFailsIfAllAccountsAreExhausted(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'GitHub remaining rate limit dropped below the minimum (10) for all accounts (first-bot: 0, second-bot: 5).'
        );

        $this->setupGitHub(['first-bot' => 0, 'second-bot' => 5]);
    }
}
