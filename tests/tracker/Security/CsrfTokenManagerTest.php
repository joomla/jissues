<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Security;

use Joomla\Input\Input;
use Joomla\Router\Exception\MethodNotAllowedException;
use JTracker\Security\CsrfTokenManager;
use JTracker\Security\Exception\InvalidCsrfTokenException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for the CSRF token manager.
 *
 * @since  __DEPLOY_VERSION__
 */
class CsrfTokenManagerTest extends TestCase
{
    /**
     * @var    CsrfTokenManager
     * @since  __DEPLOY_VERSION__
     */
    private $manager;

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    protected function setUp(): void
    {
        $this->manager = new CsrfTokenManager(new Session(new MockArraySessionStorage()));
    }

    /**
     * Build a request input object.
     *
     * @param   string  $method  The request method.
     * @param   array   $post    The POST data.
     * @param   array   $server  Additional server variables.
     *
     * @return  Input
     *
     * @since   __DEPLOY_VERSION__
     */
    private function buildInput(string $method, array $post = [], array $server = []): Input
    {
        $input = new class ([]) extends Input {
            public function seed(string $name, Input $value): void
            {
                $this->inputs[$name] = $value;
            }
        };

        $input->seed('server', new Input(array_merge(['REQUEST_METHOD' => $method], $server)));
        $input->seed('post', new Input($post));

        return $input;
    }

    /**
     * @testdox  The token is generated once per session and is stable
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testTokenIsStablePerSession(): void
    {
        $token = $this->manager->getToken();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
        $this->assertSame($token, $this->manager->getToken());
        $this->assertTrue($this->manager->isTokenValid($token));
    }

    /**
     * @testdox  Refreshing the token invalidates the old one
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testRefreshTokenInvalidatesOldToken(): void
    {
        $old = $this->manager->getToken();
        $new = $this->manager->refreshToken();

        $this->assertNotSame($old, $new);
        $this->assertFalse($this->manager->isTokenValid($old));
        $this->assertTrue($this->manager->isTokenValid($new));
    }

    /**
     * @testdox  Empty or wrong tokens are rejected
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testInvalidTokensAreRejected(): void
    {
        // No token generated yet
        $this->assertFalse($this->manager->isTokenValid(''));
        $this->assertFalse($this->manager->isTokenValid(null));

        $this->manager->getToken();

        $this->assertFalse($this->manager->isTokenValid(''));
        $this->assertFalse($this->manager->isTokenValid(str_repeat('0', 64)));
    }

    /**
     * @testdox  A POST request with the token in the form data is accepted
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testValidateRequestAcceptsFormField(): void
    {
        $input = $this->buildInput('POST', [CsrfTokenManager::FORM_FIELD => $this->manager->getToken()]);

        $this->manager->validateRequest($input);

        $this->addToAssertionCount(1);
    }

    /**
     * @testdox  A POST request with the token in the HTTP header is accepted
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testValidateRequestAcceptsHeader(): void
    {
        $input = $this->buildInput('POST', [], ['HTTP_X_CSRF_TOKEN' => $this->manager->getToken()]);

        $this->manager->validateRequest($input);

        $this->addToAssertionCount(1);
    }

    /**
     * @testdox  A GET request is rejected even when it carries a valid token
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testValidateRequestRejectsGet(): void
    {
        $input = $this->buildInput('GET', [], ['HTTP_X_CSRF_TOKEN' => $this->manager->getToken()]);

        $this->expectException(MethodNotAllowedException::class);
        $this->expectExceptionCode(405);

        $this->manager->validateRequest($input);
    }

    /**
     * @testdox  A POST request without a valid token is rejected
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public function testValidateRequestRejectsMissingToken(): void
    {
        $this->manager->getToken();

        $input = $this->buildInput('POST', [CsrfTokenManager::FORM_FIELD => 'invalid']);

        $this->expectException(InvalidCsrfTokenException::class);
        $this->expectExceptionCode(403);

        $this->manager->validateRequest($input);
    }
}
