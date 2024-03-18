<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Http;

use Joomla\Http\Exception\InvalidResponseCodeException;
use Joomla\Http\Response;
use Joomla\Http\Transport\Curl;
use Joomla\Uri\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Logger aware curl transport
 *
 * @since  1.0
 */
class CurlTransport extends Curl implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Get the logger.
     *
     * @return  LoggerInterface
     *
     * @since   1.0
     */
    public function getLogger()
    {
        // If a logger hasn't been set, use NullLogger
        if (!($this->logger instanceof LoggerInterface)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * Method to get a response object from a server response.
     *
     * @param   string  $content  The complete server response, including headers
     *                            as a string if the response has no errors.
     * @param   array   $info     The cURL request information.
     *
     * @return  Response
     *
     * @since   1.0
     * @throws  InvalidResponseCodeException
     */
    protected function getResponse($content, $info)
    {
        $this->getLogger()->debug(
            'Building response for curl request',
            [
                'response'       => $content,
                'transport_info' => $info,
            ]
        );

        return parent::getResponse($content, $info);
    }

    /**
     * Send a request to the server and return a Response object with the response.
     *
     * @param   string        $method     The HTTP method for sending the request.
     * @param   UriInterface  $uri        The URI to the resource to request.
     * @param   mixed         $data       Either an associative array or a string to be sent with the request.
     * @param   array         $headers    An array of request headers to send with the request.
     * @param   integer       $timeout    Read timeout in seconds.
     * @param   string        $userAgent  The optional user agent string to send with the request.
     *
     * @return  Response
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    public function request($method, UriInterface $uri, $data = null, array $headers = [], $timeout = null, $userAgent = null)
    {
        $this->getLogger()->debug(
            'Request started for curl transport',
            [
                'method'  => $method,
                'uri'     => (string) $uri,
                'data'    => $data,
                'headers' => $headers,
            ]
        );

        return parent::request($method, $uri, $data, $headers, $timeout, $userAgent);
    }
}
