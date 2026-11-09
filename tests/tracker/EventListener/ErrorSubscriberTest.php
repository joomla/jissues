<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\EventListener;

use App\Debug\TrackerDebugger;
use App\Projects\TrackerProject;
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationErrorEvent;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use Joomla\Router\Exception\MethodNotAllowedException;
use Joomla\Router\Exception\RouteNotFoundException;
use JTracker\Application\Application;
use JTracker\Authentication\Exception\AuthenticationException;
use JTracker\Authentication\GitHub\GitHubUser;
use JTracker\EventListener\ErrorSubscriber;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

/**
 * Tests for the ErrorSubscriber
 *
 * @since  1.0
 *
 * The shared test doubles created in setUp() only get expectations in some of the tests.
 */
#[AllowMockObjectsWithoutExpectations]
class ErrorSubscriberTest extends TestCase
{
    /**
     * Mocked Twig environment
     *
     * @var    Environment|MockObject
     * @since  1.0
     */
    private $twig;

    /**
     * Mocked logger
     *
     * @var    LoggerInterface|MockObject
     * @since  1.0
     */
    private $logger;

    /**
     * Subscriber under test
     *
     * @var    ErrorSubscriber
     * @since  1.0
     */
    private $subscriber;

    /**
     * Backup of the server superglobal
     *
     * @var    array
     * @since  1.0
     */
    private $serverBackup;

    /**
     * Sets up the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST']      = 'issues.joomla.org';
        $_SERVER['REQUEST_URI']    = '/tracker/joomla-cms/12345';
        $_SERVER['SCRIPT_NAME']    = '/index.php';

        $this->twig     = $this->createMock(Environment::class);
        $this->logger   = $this->createMock(LoggerInterface::class);

        $this->subscriber = new ErrorSubscriber($this->twig);
        $this->subscriber->setLogger($this->logger);
    }

    /**
     * Tears down the fixture.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    /**
     * Creates the web application.
     *
     * @param   array  $request  The request data
     *
     * @return  Application
     *
     * @since   1.0
     */
    private function createApplication(array $request = []): Application
    {
        $app = new Application(new Session(new MockArraySessionStorage()), new Input($request), new Registry());
        $app->set('uri.route', 'tracker/joomla-cms/12345');

        return $app;
    }

    /**
     * Dispatches an error to the subscriber.
     *
     * @param   \Throwable   $error  The error
     * @param   Application  $app    The application
     *
     * @return  void
     *
     * @since   1.0
     */
    private function handle(\Throwable $error, Application $app): void
    {
        $this->subscriber->handleWebError(new ApplicationErrorEvent($error, $app));
    }

    /**
     * @return  void
     */
    #[TestDox('The subscriber listens to the application error event')]
    public function testGetSubscribedEvents(): void
    {
        $this->assertSame([ApplicationEvents::ERROR => 'handleWebError'], ErrorSubscriber::getSubscribedEvents());
    }

    /**
     * Data provider for testStatusCodeMapping
     *
     * @return  \Generator
     */
    public static function statusCodeProvider(): \Generator
    {
        yield 'unauthorized' => [new \RuntimeException('Login required', 401), 401];
        yield 'forbidden' => [new \RuntimeException('Forbidden', 403), 403];
        yield 'not found' => [new \RuntimeException('Item not found', 404), 404];
        yield 'method not allowed code' => [new \RuntimeException('Not allowed', 405), 405];
        yield 'server error' => [new \RuntimeException('Server error', 500), 500];
        yield 'no code' => [new \RuntimeException('Something broke'), 500];
        yield 'other HTTP code' => [new \RuntimeException('Teapot', 418), 500];
        yield 'PHP error' => [new \TypeError('Wrong type'), 500];
    }

    /**
     * @param   \Throwable  $error           The error
     * @param   integer     $expectedStatus  The expected response status
     *
     * @return  void
     */
    #[TestDox('The error code is mapped to the response status and the error is logged')]
    #[DataProvider('statusCodeProvider')]
    public function testStatusCodeMapping(\Throwable $error, int $expectedStatus): void
    {
        $app = $this->createApplication();

        $this->logger->expects($this->once())
            ->method('error')
            ->with(\sprintf('Uncaught Throwable of type %s caught.', \get_class($error)), ['exception' => $error]);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('exception.twig', ['exception' => $error, 'showDetails' => false])
            ->willReturn('<h1>Error page</h1>');

        $this->handle($error, $app);

        $response = $app->getResponse();

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame($expectedStatus, $response->getStatusCode());
        $this->assertSame('<h1>Error page</h1>', (string) $response->getBody());
        $this->assertFalse($app->allowCache(), 'Error responses must not be cached');
    }

    /**
     * Data provider for testJsonResponse
     *
     * @return  \Generator
     */
    public static function jsonRequestProvider(): \Generator
    {
        yield 'json format requested' => [['_format' => 'json'], 'text/html', false];
        yield 'json mime type' => [[], 'application/json', false];
        yield 'json response' => [[], 'text/html', true];
    }

    /**
     * @param   array    $request       The request data
     * @param   string   $mimeType      The application mime type
     * @param   boolean  $jsonResponse  Whether the application already has a JSON response
     *
     * @return  void
     */
    #[TestDox('JSON requests get a JSON error response')]
    #[DataProvider('jsonRequestProvider')]
    public function testJsonResponse(array $request, string $mimeType, bool $jsonResponse): void
    {
        $app           = $this->createApplication($request);
        $app->mimeType = $mimeType;

        if ($jsonResponse) {
            $app->setResponse(new JsonResponse([]));
        }

        $error = new \RuntimeException('Item not found', 404);

        $this->twig->expects($this->never())->method('render');

        $this->handle($error, $app);

        $response = $app->getResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame(
            ['code' => 404, 'message' => 'Item not found', 'error' => true],
            json_decode((string) $response->getBody(), true)
        );
    }

    /**
     * @return  void
     */
    #[TestDox('A route that is not found is logged with the route and gets a 404 response')]
    public function testRouteNotFound(): void
    {
        $app   = $this->createApplication();
        $error = new RouteNotFoundException('Unable to handle request for route `tracker/joomla-cms/12345`.', 404);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Route `tracker/joomla-cms/12345` not found', ['exception' => $error]);

        $this->twig->method('render')->willReturn('Not found');

        $this->handle($error, $app);

        $this->assertSame(404, $app->getResponse()->getStatusCode());
        $this->assertInstanceOf(HtmlResponse::class, $app->getResponse());
    }

    /**
     * @return  void
     */
    #[TestDox('A method that is not allowed is logged with route and method and gets a 405 response with an Allow header')]
    public function testMethodNotAllowed(): void
    {
        $app   = $this->createApplication();
        $error = new MethodNotAllowedException(['get', 'put'], 'Route `tracker/joomla-cms/12345` does not support `POST` requests.');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Route `tracker/joomla-cms/12345` not supported by method `POST`', ['exception' => $error]);

        $this->twig->method('render')->willReturn('Not allowed');

        $this->handle($error, $app);

        $response = $app->getResponse();

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, PUT', $response->getHeaderLine('Allow'));
    }

    /**
     * @return  void
     */
    #[TestDox('An authentication failure is logged with the user and action and gets a 403 response')]
    public function testAuthenticationFailure(): void
    {
        $db      = $this->createStub(DatabaseDriver::class);
        $user    = new GitHubUser(new TrackerProject($db), $db);

        $user->id       = 7;
        $user->username = 'octocat';

        $app   = $this->createApplication(['_format' => 'json']);
        $error = new AuthenticationException($user, 'manage');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Authentication error',
                [
                    'exception' => $error,
                    'action'    => 'manage',
                    'user'      => 'octocat',
                    'id'        => 7,
                ]
            );

        $this->handle($error, $app);

        $response = $app->getResponse();

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(
            ['code' => 403, 'message' => 'Authentication failure', 'error' => true],
            json_decode((string) $response->getBody(), true)
        );
    }

    /**
     * @return  void
     */
    #[TestDox('An authentication failure of a guest is logged without user data')]
    public function testAuthenticationFailureForGuest(): void
    {
        $db    = $this->createStub(DatabaseDriver::class);
        $error = new AuthenticationException(new GitHubUser(new TrackerProject($db), $db), 'view');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Authentication error', ['exception' => $error, 'action' => 'view']);

        $this->twig->method('render')->willReturn('Forbidden');

        $app = $this->createApplication();

        $this->handle($error, $app);

        $this->assertSame(403, $app->getResponse()->getStatusCode());
    }

    /**
     * @return  void
     */
    #[TestDox('A profiler mark is added when system debugging is enabled')]
    public function testMarksTheProfiler(): void
    {
        $debugger = $this->createMock(TrackerDebugger::class);
        $debugger->expects($this->once())
            ->method('mark')
            ->with('Handling Throwable: ' . \DomainException::class);

        $container = new Container();
        $container->set('debugger', $debugger);

        $app = $this->createApplication();
        $app->set('debug.system', true);
        $app->setContainer($container);

        $this->twig->method('render')->willReturn('Error');

        $this->handle(new \DomainException('Broken'), $app);

        $this->assertSame(500, $app->getResponse()->getStatusCode());
    }
}
