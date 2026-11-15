<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\Mattermost;

use Joomla\Http\Http;
use Joomla\Http\Response;
use JTracker\Mattermost\MattermostNotifier;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Mattermost notifier.
 *
 * @since  1.0
 */
class MattermostNotifierTest extends TestCase
{
    #[TestDox('The message is posted as JSON to the webhook, with the channel if one is set')]
    public function testPostsJson(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->once())
            ->method('post')
            ->with(
                'https://chat.example.org/hooks/abc',
                '{"text":"Hello · #1","channel":"testing"}',
                ['Content-Type' => 'application/json']
            )
            ->willReturn(new Response('php://memory', 200));

        (new MattermostNotifier($http, 'https://chat.example.org/hooks/abc', 'testing'))->post('Hello · #1');
    }

    #[TestDox('Without a channel the webhook\'s default channel is used')]
    public function testPostsWithoutChannel(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->once())
            ->method('post')
            ->with($this->anything(), '{"text":"Hello"}')
            ->willReturn(new Response('php://memory', 200));

        (new MattermostNotifier($http, 'https://chat.example.org/hooks/abc'))->post('Hello');
    }

    #[TestDox('An error response is reported')]
    public function testThrowsOnErrorResponse(): void
    {
        $http = $this->createStub(Http::class);
        $http->method('post')->willReturn(new Response('php://memory', 403));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mattermost answered with 403');

        (new MattermostNotifier($http, 'https://chat.example.org/hooks/abc'))->post('Hello');
    }
}
