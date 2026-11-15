<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Mattermost;

use Joomla\Http\Http;

/**
 * Posts messages to a Mattermost incoming webhook.
 *
 * @since  1.0
 */
class MattermostNotifier
{
    /**
     * The HTTP client
     *
     * @var    Http
     * @since  1.0
     */
    private $http;

    /**
     * The URL of the incoming webhook
     *
     * @var    string
     * @since  1.0
     */
    private $webhookUrl;

    /**
     * The channel to post to, empty for the webhook's default channel
     *
     * @var    string
     * @since  1.0
     */
    private $channel;

    /**
     * Constructor
     *
     * @param   Http    $http        The HTTP client.
     * @param   string  $webhookUrl  The URL of the incoming webhook.
     * @param   string  $channel     The channel to post to, empty for the webhook's default channel.
     *
     * @since   1.0
     */
    public function __construct(Http $http, string $webhookUrl, string $channel = '')
    {
        $this->http       = $http;
        $this->webhookUrl = $webhookUrl;
        $this->channel    = $channel;
    }

    /**
     * Post a message.
     *
     * @param   string  $text  The message, Markdown.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  \RuntimeException if Mattermost doesn't accept the message.
     */
    public function post(string $text): void
    {
        $payload = ['text' => $text];

        if ($this->channel !== '') {
            $payload['channel'] = $this->channel;
        }

        $response = $this->http->post(
            $this->webhookUrl,
            json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ['Content-Type' => 'application/json'],
            10
        );

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \RuntimeException(
                \sprintf('Mattermost answered with %d: %s', $response->getStatusCode(), substr((string) $response->getBody(), 0, 200))
            );
        }
    }
}
