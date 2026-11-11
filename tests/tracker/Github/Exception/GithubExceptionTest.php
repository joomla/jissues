<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Github\Exception;

use Joomla\Http\Response;
use JTracker\Github\Exception\GithubException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the GitHub exception.
 *
 * @since  1.0
 */
class GithubExceptionTest extends TestCase
{
    /**
     * Create a response.
     *
     * @param   integer  $code  The status code.
     * @param   string   $body  The body.
     *
     * @return  Response
     *
     * @since   1.0
     */
    private function response(int $code, string $body): Response
    {
        $response = new Response('php://memory', $code);
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * The message of a GitHub error response is used as the exception message.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUsesTheMessageFromGitHub(): void
    {
        $response  = $this->response(401, '{"message":"Bad credentials","documentation_url":"https://docs.github.com/rest"}');
        $exception = new GithubException($response);

        $this->assertSame('Bad credentials', $exception->getMessage());
        $this->assertSame(401, $exception->getCode());
        $this->assertSame($response, $exception->getResponse());
    }

    /**
     * Data provider for bodies without a usable message.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function bodiesWithoutMessageProvider(): array
    {
        return [
            'empty body'      => [''],
            'no JSON'         => ['<html>Bad gateway</html>'],
            'no message'      => ['{"errors":[]}'],
            'empty message'   => ['{"message":""}'],
            'message no text' => ['{"message":{"text":"nested"}}'],
            'JSON list'       => ['["Bad credentials"]'],
        ];
    }

    /**
     * Without a message in the body, a generic message is used.
     *
     * @param   string  $body  The response body.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('bodiesWithoutMessageProvider')]
    public function testFallsBackToAGenericMessage(string $body): void
    {
        $exception = new GithubException($this->response(502, $body));

        $this->assertSame('Invalid response received from GitHub.', $exception->getMessage());
        $this->assertSame(502, $exception->getCode());
    }
}
