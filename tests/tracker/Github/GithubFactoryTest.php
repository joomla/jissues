<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Github;

use App\Projects\TrackerProject;
use Joomla\Application\AbstractApplication;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use JTracker\Github\Github;
use JTracker\Github\GithubFactory;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the credential handling of \JTracker\Github\GithubFactory.
 *
 * @since  1.0
 */
class GithubFactoryTest extends TestCase
{
    /**
     * Create an application stub with the given configuration.
     *
     * @param   array  $config  The configuration values.
     *
     * @return  AbstractApplication
     *
     * @since   1.0
     */
    private function createApplication(array $config): AbstractApplication
    {
        $app = $this->createStub(AbstractApplication::class);

        $app->method('get')->willReturnCallback(
            static function ($key, $default = null) use ($config) {
                return $config[$key] ?? $default;
            }
        );

        return $app;
    }

    /**
     * Create a project with the given edit bot credentials.
     *
     * @param   string  $user      The edit bot user name.
     * @param   string  $password  The edit bot password.
     * @param   string  $token     The edit bot token.
     *
     * @return  TrackerProject
     *
     * @since   1.0
     */
    private function createProject(string $user, string $password, string $token): TrackerProject
    {
        return new TrackerProject(
            $this->createStub(DatabaseDriver::class),
            (object) [
                'project_id'       => 1,
                'gh_editbot_user'  => $user,
                'gh_editbot_pass'  => $password,
                'gh_editbot_token' => $token,
            ]
        );
    }

    /**
     * Assert the credentials of a GitHub object.
     *
     * @param   Github  $github    The GitHub object.
     * @param   string  $username  The expected user name.
     * @param   string  $password  The expected password.
     * @param   string  $token     The expected token.
     *
     * @return  void
     *
     * @since   1.0
     */
    private function assertCredentials(Github $github, string $username, string $password, string $token): void
    {
        $this->assertSame($username, (string) $github->getOption('api.username'));
        $this->assertSame($password, (string) $github->getOption('api.password'));
        $this->assertSame($token, (string) $github->getOption('gh.token'));
    }

    /**
     * Test that a token from the accounts list is sent as token instead of the password.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAccountTokenTakesPrecedenceOverPassword(): void
    {
        $accounts = [
            (object) ['username' => 'bot-1', 'password' => 'old-password', 'token' => 'ghp_one'],
            (object) ['username' => 'bot-2', 'token' => 'ghp_two'],
        ];

        $github = GithubFactory::getInstance($this->createApplication(['github.accounts' => $accounts]));

        $this->assertCredentials($github, 'bot-1', '', 'ghp_one');
        $this->assertSame($accounts, $github->getOption('api.accounts'));
    }

    /**
     * Test that an account with only a password still uses basic authentication.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testAccountWithPasswordUsesBasicAuthentication(): void
    {
        $accounts = [(object) ['username' => 'bot-1', 'password' => 'ghp_as_password']];

        $github = GithubFactory::getInstance($this->createApplication(['github.accounts' => $accounts]));

        $this->assertCredentials($github, 'bot-1', 'ghp_as_password', '');
    }

    /**
     * Test the single account configuration with a token.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSingleAccountToken(): void
    {
        $github = GithubFactory::getInstance(
            $this->createApplication(['github.username' => 'bot', 'github.token' => 'ghp_single'])
        );

        $this->assertCredentials($github, 'bot', '', 'ghp_single');
    }

    /**
     * Test that no credentials are set without a configured account.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testNoCredentials(): void
    {
        $github = GithubFactory::getInstance($this->createApplication([]));

        $this->assertCredentials($github, '', '', '');
    }

    /**
     * Test that a bot token passed in is preferred over the configured accounts.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBotTokenIsPreferredOverConfiguration(): void
    {
        $app = $this->createApplication(['github.accounts' => [(object) ['username' => 'config-bot', 'token' => 'ghp_config']]]);

        $github = GithubFactory::getInstance($app, true, 'edit-bot', '', 'ghp_bot');

        $this->assertCredentials($github, 'edit-bot', '', 'ghp_bot');
        $this->assertNull($github->getOption('api.accounts'));
    }

    /**
     * Test the project edit bot instance.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testProjectBotInstanceUsesToken(): void
    {
        $app = $this->createApplication(['github.accounts' => [(object) ['username' => 'config-bot', 'token' => 'ghp_config']]]);

        $github = GithubFactory::getProjectBotInstance($app, $this->createProject('edit-bot', 'old-password', 'ghp_bot'));

        $this->assertCredentials($github, 'edit-bot', '', 'ghp_bot');
    }

    /**
     * Test the project edit bot instance with the deprecated password.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testProjectBotInstanceWithPassword(): void
    {
        $github = GithubFactory::getProjectBotInstance(
            $this->createApplication([]),
            $this->createProject('edit-bot', 'ghp_as_password', '')
        );

        $this->assertCredentials($github, 'edit-bot', 'ghp_as_password', '');
    }

    /**
     * Test that the configured account is used if the project has no edit bot.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testProjectBotInstanceFallsBackToConfiguration(): void
    {
        $app = $this->createApplication(['github.accounts' => [(object) ['username' => 'config-bot', 'token' => 'ghp_config']]]);

        $github = GithubFactory::getProjectBotInstance($app, $this->createProject('edit-bot', '', ''));

        $this->assertCredentials($github, 'config-bot', '', 'ghp_config');
    }

    /**
     * Test that switching accounts replaces the previous credentials.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSetCredentialsReplacesPreviousAccount(): void
    {
        $options = new Registry();

        GithubFactory::setCredentials($options, 'bot-1', '', 'ghp_one');
        GithubFactory::setCredentials($options, 'bot-2', 'ghp_as_password');

        $this->assertSame('bot-2', $options->get('api.username'));
        $this->assertSame('ghp_as_password', $options->get('api.password'));
        $this->assertSame('', (string) $options->get('gh.token'));

        GithubFactory::setCredentials($options, 'bot-3', 'ignored', 'ghp_three');

        $this->assertSame('bot-3', $options->get('api.username'));
        $this->assertSame('', (string) $options->get('api.password'));
        $this->assertSame('ghp_three', $options->get('gh.token'));
    }
}
