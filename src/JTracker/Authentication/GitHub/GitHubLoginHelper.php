<?php

/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Authentication\GitHub;

use Joomla\Database\DatabaseDriver;
use Joomla\Github\Github;
use Joomla\Http\Http;
use Joomla\Uri\Uri;
use JTracker\Application\Application;

/**
 * Helper class for logging into the application via GitHub.
 *
 * @since  1.0
 */
class GitHubLoginHelper
{
    /**
     * Path to locally stored user avatars
     *
     * @var    string
     * @since  1.0
     */
    private const AVATAR_PATH = JPATH_THEMES . '/images/avatars';

    /**
     * Application object
     *
     * @var    Application
     * @since  1.0
     */
    private $application;

    /**
     * The authentication scope
     *
     * @var    string
     * @since  1.0
     */
    private $authScope;

    /**
     * The client ID
     *
     * @var    string
     * @since  1.0
     */
    private $clientId;

    /**
     * The client secret
     *
     * @var    string
     * @since  1.0
     */
    private $clientSecret;

    /**
     * Database driver
     *
     * @var    DatabaseDriver
     * @since  1.0
     */
    private $db;

    /**
     * GitHub API Client
     *
     * @var    Github
     * @since  1.0
     */
    private $github;

    /**
     * HTTP Client
     *
     * @var    Http
     * @since  1.0
     */
    private $http;

    /**
     * Path to user avatars
     *
     * @var    string
     * @since  1.0
     */
    private $avatarPath = '';

    /**
     * Constructor.
     *
     * @param   WebApplication  $application   Application object.
     * @param   DatabaseDriver  $db            Database driver.
     * @param   Github          $github        GitHub API Client.
     * @param   Http            $http          HTTP Client.
     * @param   string          $clientId      The client ID.
     * @param   string          $clientSecret  The client secret.
     * @param   string          $authScope     The authentication scope.
     */
    public function __construct(
        Application $application,
        DatabaseDriver $db,
        Github $github,
        Http $http,
        string $clientId,
        string $clientSecret,
        string $authScope
    ) {
        $this->application  = $application;
        $this->db           = $db;
        $this->github       = $github;
        $this->http         = $http;
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
        $this->authScope    = $authScope;
    }

    /**
     * Method to retrieve the correct URI for login via GitHub
     *
     * @return  string  The login URI
     *
     * @since   1.0
     */
    public function getLoginUri()
    {
        if (!$this->clientId) {
            // No clientId set - Throw some fatal error...
            return '';
        }

        $uri = new Uri($this->application->get('uri.base.full') . 'login');
        $uri->setVar('usr_redirect', base64_encode((string) new Uri($this->application->get('uri.request'))));

        return $this->github->authorization->getAuthorizationLink(
            $this->clientId,
            (string) $uri,
            $this->authScope
        );
    }

    /**
     * Request an oAuth token from GitHub.
     *
     * @param   string  $code  The code obtained form GitHub on the previous step.
     *
     * @return  string  The OAuth token
     *
     * @since   1.0
     * @throws  \RuntimeException
     * @throws  \DomainException
     */
    public function requestToken(string $code): string
    {
        $data = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code'          => $code,
        ];

        $response = $this->http->post(
            'https://github.com/login/oauth/access_token',
            $data,
            ['Accept' => 'application/json']
        );

        if ($response->code !== 200) {
            throw new \DomainException('Invalid response from GitHub (2) :(');
        }

        $body = json_decode($response->body);

        if (isset($body->error)) {
            switch ($body->error) {
                case 'bad_verification_code':
                    throw new \DomainException('bad verification code');

                default:
                    throw new \DomainException('Unknown (2) ' . $body->error);
            }
        }

        if (!isset($body->access_token)) {
            throw new \DomainException('Cannot retrieve the access token');
        }

        return $body->access_token;
    }

    /**
     * Save an avatar.
     *
     * NOTE: A redirect is expected while fetching the avatar.
     *
     * @param   string   $username      The username to retrieve the avatar for.
     * @param   boolean  $forceRefresh  Force refreshing the avatar.
     *
     * @return  integer  The function returns the number of bytes that were written to the file, or false on failure.
     *
     * @since   1.0
     * @throws  \RuntimeException
     * @throws  \DomainException
     */
    public function saveAvatar($username, bool $forceRefresh = false)
    {
        $path = self::AVATAR_PATH . '/' . $username . '.png';

        if (file_exists($path)) {
            if (!$forceRefresh) {
                return 1;
            }

            if (unlink($path) === false) {
                throw new \DomainException('Can not remove: ' . $path);
            }
        }

        $response = $this->http->get($this->github->users->get($username)->avatar_url);

        if ($response->code != 200) {
            throw new \DomainException(\sprintf('Can not retrieve the avatar for user %s', $username));
        }

        $result = file_put_contents($path, $response->body);

        if ($result === false) {
            throw new \RuntimeException(\sprintf('Can not write the avatar image to file %s', $path));
        }

        return $result;
    }

    /**
     * Get an avatar path.
     *
     * @param   GitHubUser  $user  The user.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function getAvatarPath(GitHubUser $user)
    {
        static $avatars = [];

        if (\array_key_exists($user->username, $avatars)) {
            return $avatars[$user->username];
        }

        $path = self::AVATAR_PATH . '/' . $user->username . '.png';

        $avatars[$user->username] = file_exists($path) ? $path : self::AVATAR_PATH . '/user-default.png';

        return $avatars[$user->username];
    }

    /**
     * Refresh local user information with data from GitHub.
     *
     * @param   GitHubUser  $user  The GitHub user object.
     *
     * @return  $this
     */
    public function refreshUser(GitHubUser $user)
    {
        // Refresh the avatar
        $path = self::AVATAR_PATH . '/' . $user->username . '.png';

        if (file_exists($path)) {
            if (unlink($path) === false) {
                throw new \DomainException('Can not remove: ' . $path);
            }
        }

        $this->saveAvatar($user->username, true);

        // Refresh user data in database.
        $db = $this->db;

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__users'))
                ->set($db->quoteName('email') . '=' . $db->quote($user->email))
                ->set($db->quoteName('name') . '=' . $db->quote($user->name))
                ->where($db->quoteName('id') . '=' . (int) $user->id)
        )->execute();

        return $this;
    }

    /**
     * Set the last visited time for a newly logged in user
     *
     * @param   integer  $id  The user ID to update
     *
     * @return  void
     *
     * @since   1.0
     */
    public function setLastVisitTime($id)
    {
        $db          = $this->db;
        $currentTime = (new \DateTime('now', new \DateTimeZone('UTC')))->format($db->getDateFormat());

        $db->setQuery(
            $db->getQuery(true)
                ->update($db->quoteName('#__users'))
                ->set($db->quoteName('lastvisitDate') . '=' . $db->quote($currentTime))
                ->where($db->quoteName('id') . '=' . (int) $id)
        )->execute();
    }
}
