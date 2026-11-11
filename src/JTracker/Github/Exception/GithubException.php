<?php

/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Exception;

use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Response;

/**
 * Class GithubException
 *
 * @since  1.0
 */
class GithubException extends UnexpectedResponseException
{
    /**
     * Constructor.
     *
     * @param   Response  $response  The response object.
     *
     * @since  1.0
     */
    public function __construct(Response $response)
    {
        $error = json_decode((string) $response->getBody());
        $code  = $response->getStatusCode();

        // GitHub returns errors as JSON with a message, e.g. {"message": "Bad credentials"}
        $message = \is_object($error) && \is_string($error->message ?? null) && $error->message !== ''
            ? $error->message
            : 'Invalid response received from GitHub.';

        parent::__construct($response, $message, $code);
    }
}
