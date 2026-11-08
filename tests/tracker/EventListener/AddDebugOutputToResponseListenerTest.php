<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\EventListener;

use App\Debug\TrackerDebugger;
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use JTracker\Application\Application;
use JTracker\EventListener\AddDebugOutputToResponseListener;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for the AddDebugOutputToResponseListener
 *
 * The listener reads the global JDEBUG constant, so each test defining it runs in a separate process.
 *
 * @since  1.0
 */
class AddDebugOutputToResponseListenerTest extends TestCase
{
    /**
     * Runs the listener for an application with the given response.
     *
     * @param   boolean  $debug     The JDEBUG value
     * @param   object   $response  The application response
     * @param   string   $mimeType  The application mime type
     *
     * @return  Application
     *
     * @since   1.0
     */
    private function runListener(bool $debug, object $response, string $mimeType = 'text/html'): Application
    {
        \define('JDEBUG', $debug);

        // The web application detects its URIs from the request, this test runs in an isolated process
        $_SERVER['HTTP_HOST']   = 'issues.joomla.org';
        $_SERVER['REQUEST_URI'] = '/tracker/joomla-cms';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $debugger = $this->createMock(TrackerDebugger::class);
        $debugger->method('getOutput')->willReturn('<div id="debug">Queries: 3</div>');

        $app = new Application(new Session(new MockArraySessionStorage()), new Input([]), new Registry());
        $app->setResponse($response);
        $app->mimeType = $mimeType;

        (new AddDebugOutputToResponseListener($debugger))(new ApplicationEvent(ApplicationEvents::BEFORE_RESPOND, $app));

        return $app;
    }

    /**
     * @testdox  The debug output replaces the placeholder in HTML responses when debugging
     *
     * @return  void
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function testAddsDebugOutputToHtmlResponse(): void
    {
        $app = $this->runListener(true, new HtmlResponse('<body>Page%%%DEBUG%%%</body>'));

        $this->assertSame('<body>Page<div id="debug">Queries: 3</div></body>', $app->getBody());
    }

    /**
     * @testdox  The placeholder is removed from HTML responses when not debugging
     *
     * @return  void
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function testRemovesPlaceholderWithoutDebugging(): void
    {
        $app = $this->runListener(false, new HtmlResponse('<body>Page%%%DEBUG%%%</body>'));

        $this->assertSame('<body>Page</body>', $app->getBody());
    }

    /**
     * @testdox  JSON responses are left untouched
     *
     * @return  void
     *
     * @runInSeparateProcess
     * @preserveGlobalState  disabled
     */
    public function testLeavesJsonResponseUntouched(): void
    {
        $app = $this->runListener(true, new JsonResponse(['text' => '%%%DEBUG%%%']), 'application/json');

        $this->assertSame('{"text":"%%%DEBUG%%%"}', $app->getBody());
    }
}
