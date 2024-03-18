<?php

/**
 * Part of the Joomla Tracker Github Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Github;

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
     * @param   \Joomla\Application\AbstractApplication  $app          Application object
     * @param   boolean                                  $useBot       Flag to use a bot account.
     * @param   string                                   $botUser      The bot account user name.
     * @param   string                                   $botPassword  The bot account password.
     *
     * @return  GitHub
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public static function getInstance($app, $useBot = false, $botUser = '', $botPassword = '')
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
        } else {
            // Otherwise fall back to an account from the system configuration
            // Check if credentials are supplied
            if ($botUser && $botPassword) {
                $user     = $botUser;
                $password = $botPassword;
            } else {
                // Check for support for multiple accounts
                $accounts = $app->get('github.accounts');

                if ($accounts) {
                    $user     = $accounts[0]->username ?? null;
                    $password = $accounts[0]->password ?? null;

                    // Store the other accounts
                    $options->set('api.accounts', $accounts);
                } else {
                    // Support for a single account
                    $user     = $app->get('github.username');
                    $password = $app->get('github.password');
                }
            }

            // Add the username and password to the options object if both are set
            if ($user && $password) {
                // Set the options from the first account
                $options->set('api.username', $user);
                $options->set('api.password', $password);
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
}
