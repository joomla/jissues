<?php

/**
 * Part of the Joomla Tracker Security Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Security;

use Joomla\Input\Input;
use Joomla\Router\Exception\MethodNotAllowedException;
use JTracker\Security\Exception\InvalidCsrfTokenException;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Manages the per-session token used to protect state changing requests against cross-site request forgery.
 *
 * @since  __DEPLOY_VERSION__
 */
class CsrfTokenManager
{
    /**
     * Name of the form field carrying the token.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    public const FORM_FIELD = 'csrf_token';

    /**
     * Name of the HTTP header carrying the token (used by AJAX requests).
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    public const HEADER = 'X-CSRF-Token';

    /**
     * Session key the token is stored under.
     *
     * @var    string
     * @since  __DEPLOY_VERSION__
     */
    private const SESSION_KEY = 'jtracker.csrf_token';

    /**
     * The session the token is stored in.
     *
     * @var    SessionInterface
     * @since  __DEPLOY_VERSION__
     */
    private $session;

    /**
     * Constructor.
     *
     * @param   SessionInterface  $session  The session the token is stored in.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Get the token for the current session, generating one if none exists yet.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getToken(): string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (!\is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));

            $this->session->set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    /**
     * Replace the token of the current session with a new one (e.g. after a login).
     *
     * @return  string  The new token.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function refreshToken(): string
    {
        $this->session->remove(self::SESSION_KEY);

        return $this->getToken();
    }

    /**
     * Check whether the given token matches the token of the current session.
     *
     * @param   string|null  $token  The token to check.
     *
     * @return  boolean
     *
     * @since   __DEPLOY_VERSION__
     */
    public function isTokenValid(?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $expected = $this->session->get(self::SESSION_KEY);

        if (!\is_string($expected) || $expected === '') {
            return false;
        }

        return hash_equals($expected, $token);
    }

    /**
     * Extract the token submitted with the request, either from the HTTP header or from the POST data.
     *
     * @param   Input  $input  The request input.
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getRequestToken(Input $input): string
    {
        $token = (string) $input->server->get('HTTP_' . strtoupper(str_replace('-', '_', self::HEADER)), '', 'raw');

        if ($token === '') {
            $token = (string) $input->post->get(self::FORM_FIELD, '', 'raw');
        }

        return $token;
    }

    /**
     * Validate that the request is a POST request carrying a valid token.
     *
     * @param   Input  $input  The request input.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     * @throws  MethodNotAllowedException  If the request is not a POST request.
     * @throws  InvalidCsrfTokenException  If the token is missing or invalid.
     */
    public function validateRequest(Input $input): void
    {
        if ($input->getMethod() !== 'POST') {
            throw new MethodNotAllowedException(['POST'], 'This action requires a POST request.', 405);
        }

        if (!$this->isTokenValid($this->getRequestToken($input))) {
            throw new InvalidCsrfTokenException();
        }
    }
}
