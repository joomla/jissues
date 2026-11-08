<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\Authentication;

use Joomla\Database\DatabaseDriver;
use Joomla\Github\Github;
use Joomla\Http\Http;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use JTracker\Application\Application;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for the OAuth state and redirect handling of GitHubLoginHelper.
 *
 * @since  1.0
 */
class GitHubLoginHelperTest extends TestCase
{
    /**
     * @var    GitHubLoginHelper
     * @since  1.0
     */
    private $helper;

    /**
     * @var    Session
     * @since  1.0
     */
    private $session;

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());

        $application = new Application($this->session, new Input([]), new Registry());
        $application->set('uri.base.full', 'https://issues.example.org/');

        $this->helper = new GitHubLoginHelper(
            $application,
            $this->createMock(DatabaseDriver::class),
            $this->createMock(Github::class),
            $this->createMock(Http::class),
            'client-id',
            'client-secret',
            'public_repo'
        );
    }

    /**
     * The state is stable within a session and can be validated exactly once.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testStateValidation(): void
    {
        $state = $this->helper->getState();

        $this->assertSame(64, \strlen($state));
        $this->assertSame($state, $this->helper->getState());

        $this->assertTrue($this->helper->validateState($state));
        $this->assertFalse($this->helper->validateState($state), 'A state must not be accepted twice.');
        $this->assertNotSame($state, $this->helper->getState(), 'A new state must be generated after validation.');
    }

    /**
     * Wrong or missing states are rejected.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testInvalidStateIsRejected(): void
    {
        $this->assertFalse($this->helper->validateState(''), 'No state stored in the session.');

        $state = $this->helper->getState();

        $this->assertFalse($this->helper->validateState('foo'));
        $this->assertFalse($this->helper->validateState($state), 'A failed validation must discard the stored state.');
    }

    /**
     * Data provider for testGetSafeRedirect.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function redirectProvider(): array
    {
        return [
            'empty'                  => ['', ''],
            'same host'              => ['https://issues.example.org/tracker/jtracker/1', 'https://issues.example.org/tracker/jtracker/1'],
            'same host other scheme' => ['http://issues.example.org/', 'http://issues.example.org/'],
            'same host uppercase'    => ['https://ISSUES.example.org/', 'https://ISSUES.example.org/'],
            'absolute path'          => ['/tracker/jtracker', '/tracker/jtracker'],
            'relative path'          => ['tracker/jtracker', 'tracker/jtracker'],
            'other host'             => ['https://example.com/', ''],
            'host suffix'            => ['https://issues.example.org.example.com/', ''],
            'other port'             => ['https://issues.example.org:8443/', ''],
            'protocol relative'      => ['//example.com/', ''],
            'backslash'              => ['/\\example.com/', ''],
            'userinfo'               => ['https://issues.example.org@example.com/', ''],
            'javascript scheme'      => ['javascript:alert(1)', ''],
            'control characters'     => ["/\t/example.com", ''],
            'scheme without host'    => ['https:example.com', ''],
        ];
    }

    /**
     * Test getSafeRedirect.
     *
     * @param   string  $redirect  The requested redirect.
     * @param   string  $expected  The expected result.
     *
     * @return  void
     *
     * @dataProvider  redirectProvider
     * @since   1.0
     */
    public function testGetSafeRedirect(string $redirect, string $expected): void
    {
        $this->assertSame($expected, $this->helper->getSafeRedirect($redirect));
    }
}
