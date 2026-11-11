<?php

/**
 * Part of the Joomla Tracker Github Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Github;

use App\Projects\TrackerProject;
use Joomla\Http\Http;
use Joomla\Registry\Registry;
use JTracker\Application\Application;
use JTracker\Http\CurlTransport;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;

/**
 * Factory class for retrieving a GitHub object
 *
 * @since  1.0
 */
abstract class GithubFactory
{
    /**
     * Retrieves an instance of the GitHub object
     *
     * Credentials are resolved in this order:
     *
     * 1. The OAuth token of the logged in user (web application only, unless a bot account is requested)
     * 2. The bot credentials passed to this method (token, or username and password)
     * 3. The first entry of `github.accounts` in the configuration
     * 4. `github.token`, or `github.username` and `github.password`, in the configuration
     *
     * Personal access tokens are sent in the `Authorization` header. Username and password authentication only works
     * if the password is a personal access token, as GitHub no longer accepts account passwords for API requests.
     *
     * @param   \Joomla\Application\AbstractApplication  $app          Application object
     * @param   boolean                                  $useBot       Flag to use a bot account.
     * @param   string                                   $botUser      The bot account user name.
     * @param   string                                   $botPassword  The bot account password (deprecated, use a token instead).
     * @param   string                                   $botToken     The bot account personal access token.
     *
     * @return  GitHub
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public static function getInstance($app, $useBot = false, $botUser = '', $botPassword = '', $botToken = '')
    {
        $options = new Registry();

        // Check if we're in the web application and a token exists
        if ($app instanceof Application) {
            $session = $app->getSession();

            $token = $session->get('gh_oauth_access_token');
        } else {
            $token = false;
        }

        // If a token is active in the session (web app), and we haven't been instructed to use a bot account, use that for authentication
        if ($token && !$useBot) {
            $options->set('gh.token', $token);
        } elseif ($botToken || ($botUser && $botPassword)) {
            // Use the credentials which were passed in
            static::setCredentials($options, $botUser, $botPassword, $botToken);
        } else {
            // Otherwise fall back to an account from the system configuration, checking for support for multiple accounts first
            $accounts = $app->get('github.accounts');

            if ($accounts) {
                static::setCredentials(
                    $options,
                    $accounts[0]->username ?? '',
                    $accounts[0]->password ?? '',
                    $accounts[0]->token ?? ''
                );

                // Store the other accounts
                $options->set('api.accounts', $accounts);
            } else {
                // Support for a single account
                static::setCredentials(
                    $options,
                    $app->get('github.username', ''),
                    $app->get('github.password', ''),
                    $app->get('github.token', '')
                );
            }
        }

        // The cURL extension is required to properly work.
        $transport = new CurlTransport($options);

        $github = new Github($options, new Http($options, $transport));

        // If debugging is enabled, inject a logger
        if ($app->get('debug.github', false)) {
            $logger = new Logger(
                'JTracker-Github',
                [
                    new StreamHandler(
                        $app->get('debug.log-path', JPATH_ROOT . '/logs') . '/github.log',
                        Logger::DEBUG
                    ),
                ],
                [
                    new WebProcessor(),
                ]
            );

            $github->setLogger($logger);
            $transport->setLogger($logger);
        }

        return $github;
    }

    /**
     * Retrieves an instance of the GitHub object which acts as the edit bot of a project
     *
     * Falls back to the default instance if the project has no edit bot configured.
     *
     * @param   \Joomla\Application\AbstractApplication  $app      Application object
     * @param   TrackerProject                           $project  The project.
     *
     * @return  GitHub
     *
     * @since   1.0
     */
    public static function getProjectBotInstance($app, TrackerProject $project)
    {
        if (!$project->hasEditBot()) {
            return static::getInstance($app);
        }

        return static::getInstance(
            $app,
            true,
            (string) $project->getGh_Editbot_User(),
            (string) $project->getGh_Editbot_Pass(),
            (string) $project->getGh_Editbot_Token()
        );
    }

    /**
     * Set the credentials of a GitHub account on an options object
     *
     * A token takes precedence over username and password. Existing credentials are replaced, so this can also be used
     * to switch between accounts.
     *
     * @param   Registry  $options   The options object.
     * @param   string    $username  The account user name.
     * @param   string    $password  The account password (deprecated, use a token instead).
     * @param   string    $token     The account personal access token.
     *
     * @return  void
     *
     * @since   1.0
     */
    public static function setCredentials(Registry $options, $username = '', $password = '', $token = '')
    {
        // The user name is kept for informational purposes, it is not sent to GitHub when a token is used.
        $options->set('api.username', $username ?: '');

        if ($token) {
            $options->set('gh.token', $token);
            $options->set('api.password', '');

            return;
        }

        $options->set('gh.token', '');
        $options->set('api.password', ($username && $password) ? $password : '');
    }
}
