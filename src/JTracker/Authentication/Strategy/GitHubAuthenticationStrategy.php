<?php

/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Authentication\Strategy;

use Joomla\Authentication\Authentication;
use Joomla\Authentication\AuthenticationStrategyInterface;
use Joomla\Input\Input;
use JTracker\Authentication\GitHub\GitHubLoginHelper;

/**
 * Authentication strategy for OAuth based logins with GitHub
 *
 * @since  1.0
 */
class GitHubAuthenticationStrategy implements AuthenticationStrategyInterface
{
    /**
     * Constant identifying a GitHub authentication error
     *
     * @var    integer
     * @since  1.0
     */
    public const AUTHENTICATION_ERROR = 6;

    /**
     * The Input object
     *
     * @var    Input
     * @since  1.0
     */
    private $input;

    /**
     * GitHub login helper
     *
     * @var    GitHubLoginHelper
     * @since  1.0
     */
    private $loginHelper;

    /**
     * The last authentication status.
     *
     * @var    integer
     * @since  1.0
     */
    private $status;

    /**
     * Strategy Constructor
     *
     * @param   GitHubLoginHelper  $loginHelper  GitHub login helper.
     * @param   Input              $input        The input object from which to read data.
     *
     * @since   1.0
     */
    public function __construct(GitHubLoginHelper $loginHelper, Input $input)
    {
        $this->input       = $input;
        $this->loginHelper = $loginHelper;
    }

    /**
     * Attempt to authenticate the GitHub OAuth response.
     *
     * @return  string|boolean  A string containing the GitHub access token if successful, false otherwise.
     *
     * @since   1.0
     */
    public function authenticate()
    {
        $error = $this->input->get('error');

        if ($error) {
            $this->status = self::AUTHENTICATION_ERROR;

            return false;
        }

        $code = $this->input->get('code');

        if (!$code) {
            $this->status = self::AUTHENTICATION_ERROR;

            return false;
        }

        try {
            $accessToken = $this->loginHelper->requestToken($code);

            $this->status = Authentication::SUCCESS;

            return $accessToken;
        } catch (\Exception $exception) {
            $this->status = self::AUTHENTICATION_ERROR;

            return false;
        }
    }

    /**
     * Get the status of the last authentication attempt.
     *
     * @return  integer  Authentication class constant result.
     *
     * @since   1.0
     */
    public function getResult()
    {
        return $this->status;
    }
}
