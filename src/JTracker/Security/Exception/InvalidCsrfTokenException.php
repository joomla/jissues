<?php

/**
 * Part of the Joomla Tracker Security Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Security\Exception;

/**
 * Exception thrown when a state changing request does not carry a valid CSRF token.
 *
 * @since  __DEPLOY_VERSION__
 */
class InvalidCsrfTokenException extends \RuntimeException
{
    /**
     * Constructor.
     *
     * @param   string           $message   The exception message.
     * @param   integer          $code      The exception code.
     * @param   \Throwable|null  $previous  The previous throwable.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(
        string $message = 'Invalid or missing security token. Please reload the page and try again.',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
