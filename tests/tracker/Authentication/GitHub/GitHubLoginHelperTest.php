<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Authentication\GitHub;

use App\Projects\TrackerProject;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use Joomla\Github\Github;
use Joomla\Github\Package\Authorization;
use Joomla\Github\Package\Users;
use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use JTracker\Application\Application;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for the GitHubLoginHelper class.
 *
 * @since  1.0
 */
class GitHubLoginHelperTest extends TestCase
{
    /**
     * Directory holding the avatars, mirrors GitHubLoginHelper::AVATAR_PATH
     *
     * @var  string
     */
    private const AVATAR_PATH = JPATH_THEMES . '/images/avatars';

    /**
     * Application object
     *
     * @var  Application
     */
    private $application;

    /**
     * Mocked database driver
     *
     * @var  DatabaseDriver|MockObject
     */
    private $db;

    /**
     * Mocked HTTP client used by the GitHub API object
     *
     * @var  Http|MockObject
     */
    private $githubHttp;

    /**
     * Mocked HTTP client used by the helper directly
     *
     * @var  Http|MockObject
     */
    private $http;

    /**
     * Avatar files created during a test, removed on tear down
     *
     * @var  string[]
     */
    private $createdFiles = [];

    /**
     * Sets up the fixture.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        $this->application = new Application(new Session(new MockArraySessionStorage()), new Input([]), new Registry());
        $this->application->set('uri.base.full', 'https://issues.example.org/');
        $this->application->set('uri.request', 'https://issues.example.org/tracker/jtracker/');

        $this->db         = $this->createMock(DatabaseDriver::class);
        $this->githubHttp = $this->createMock(Http::class);
        $this->http       = $this->createMock(Http::class);
    }

    /**
     * Removes avatar files created by a test.
     *
     * @return  void
     */
    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        $this->createdFiles = [];
    }

    /**
     * Creates the helper object.
     *
     * @param   string  $clientId  The client ID
     *
     * @return  GitHubLoginHelper
     */
    private function createHelper(string $clientId = 'my-client-id'): GitHubLoginHelper
    {
        // Return real API objects without letting Github::__get() create dynamic properties
        $options = new Registry(['api' => ['url' => 'https://api.github.com']]);

        $github = $this->createMock(Github::class);
        $github->method('__get')->willReturnMap([
            ['authorization', new Authorization($options, $this->githubHttp)],
            ['users', new Users($options, $this->githubHttp)],
        ]);

        return new GitHubLoginHelper(
            $this->application,
            $this->db,
            $github,
            $this->http,
            $clientId,
            'my-client-secret',
            'public_repo'
        );
    }

    /**
     * Creates an HTTP response.
     *
     * @param   integer  $code  The status code
     * @param   string   $body  The response body
     *
     * @return  Response
     */
    private function createResponse(int $code, string $body): Response
    {
        $response = new Response('php://memory', $code);
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * Creates a user object.
     *
     * @param   string  $username  The user name
     *
     * @return  GitHubUser
     */
    private function createUser(string $username): GitHubUser
    {
        $user = new GitHubUser($this->createMock(TrackerProject::class), $this->db);

        $user->username = $username;

        return $user;
    }

    /**
     * Returns a unique avatar user name and registers its file for removal.
     *
     * @return  string
     */
    private function uniqueUsername(): string
    {
        $username = 'jtracker-unit-test-' . bin2hex(random_bytes(6));

        $this->createdFiles[] = self::AVATAR_PATH . '/' . $username . '.png';

        return $username;
    }

    /**
     * Configures the database mock to build real queries and records the executed ones.
     *
     * @param   array  $queries  Receives the queries passed to setQuery()
     *
     * @return  void
     */
    private function expectQueries(array &$queries): void
    {
        $db = $this->db;

        $db->method('getQuery')->willReturnCallback(static function () use ($db) {
            return new MysqliQuery($db);
        });
        $db->method('quoteName')->willReturnCallback(static function ($name) {
            return '`' . $name . '`';
        });
        $db->method('quote')->willReturnCallback(static function ($text) {
            return "'" . addslashes($text) . "'";
        });
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $db->method('setQuery')->willReturnCallback(static function ($query) use ($db, &$queries) {
            $queries[] = (string) $query;

            return $db;
        });
    }

    /**
     * Extracts the query variables of a URI.
     *
     * @param   string  $uri  The URI
     *
     * @return  array
     */
    private function queryVars(string $uri): array
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $vars);

        return $vars;
    }

    /**
     * @testdox  getLoginUri() returns an empty string when no client ID is configured
     *
     * @return  void
     */
    public function testGetLoginUriWithoutClientId(): void
    {
        $this->assertSame('', $this->createHelper('')->getLoginUri());
    }

    /**
     * @testdox  getLoginUri() points to the GitHub authorization page with client ID, scope and redirect URI
     *
     * @return  void
     */
    public function testGetLoginUri(): void
    {
        $loginUri = $this->createHelper()->getLoginUri();

        $this->assertStringStartsWith('https://github.com/login/oauth/authorize?', $loginUri);

        $vars = $this->queryVars($loginUri);

        $this->assertSame('my-client-id', $vars['client_id']);
        $this->assertSame('public_repo', $vars['scope']);
        $this->assertStringStartsWith('https://issues.example.org/login?usr_redirect=', $vars['redirect_uri']);
    }

    /**
     * Data provider for testGetLoginUriRedirectRoundTrip
     *
     * @return  \Generator
     */
    public function requestUriProvider(): \Generator
    {
        yield 'plain URL' => ['https://issues.example.org/tracker/jtracker/'];
        yield 'base64 contains + / and ==' => ['https://issues.joomla.org/tracker/joomla-cms/10?x=>?'];
        yield 'base64 contains + / and =' => ['https://issues.joomla.org/tracker/joomla-cms/10?q=~~~'];
    }

    /**
     * @testdox  The usr_redirect value survives the round trip through GitHub and the login controller
     *
     * @param   string  $requestUri  The URI of the page the user logs in from
     *
     * @return  void
     *
     * @dataProvider  requestUriProvider
     */
    public function testGetLoginUriRedirectRoundTrip(string $requestUri): void
    {
        $this->application->set('uri.request', $requestUri);

        $loginUri = $this->createHelper()->getLoginUri();

        // GitHub decodes its query string and redirects the browser to the given redirect_uri
        $redirectUri = $this->queryVars($loginUri)['redirect_uri'];

        // PHP decodes the query string of the callback request, the login controller reads it with getBase64()
        $input    = new Input($this->queryVars($redirectUri));
        $redirect = $input->getBase64('usr_redirect');

        $this->assertSame(base64_encode($requestUri), $redirect);
        $this->assertSame($requestUri, base64_decode($redirect));
    }

    /**
     * Verifies the test data of the round trip test exercises the problematic characters.
     *
     * @return  void
     */
    public function testRoundTripDataContainsSpecialBase64Characters(): void
    {
        $encoded = '';

        foreach ($this->requestUriProvider() as [$requestUri]) {
            $encoded .= base64_encode($requestUri);
        }

        $this->assertStringContainsString('+', $encoded);
        $this->assertStringContainsString('/', $encoded);
        $this->assertStringContainsString('=', $encoded);
    }

    /**
     * @testdox  requestToken() posts the credentials to GitHub and returns the access token
     *
     * @return  void
     */
    public function testRequestToken(): void
    {
        $this->http->expects($this->once())
            ->method('post')
            ->with(
                'https://github.com/login/oauth/access_token',
                [
                    'client_id'     => 'my-client-id',
                    'client_secret' => 'my-client-secret',
                    'code'          => 'the-code',
                ],
                ['Accept' => 'application/json']
            )
            ->willReturn($this->createResponse(200, '{"access_token":"the-token"}'));

        $this->assertSame('the-token', $this->createHelper()->requestToken('the-code'));
    }

    /**
     * Data provider for testRequestTokenErrors
     *
     * @return  \Generator
     */
    public function requestTokenErrorProvider(): \Generator
    {
        yield 'HTTP error' => [500, '{"access_token":"the-token"}', 'Invalid response from GitHub (2) :('];
        yield 'bad verification code' => [200, '{"error":"bad_verification_code"}', 'bad verification code'];
        yield 'unknown error' => [200, '{"error":"incorrect_client_credentials"}', 'Unknown (2) incorrect_client_credentials'];
        yield 'missing token' => [200, '{"scope":"public_repo"}', 'Cannot retrieve the access token'];
    }

    /**
     * @testdox  requestToken() throws on an invalid response
     *
     * @param   integer  $code     The response code
     * @param   string   $body     The response body
     * @param   string   $message  The expected exception message
     *
     * @return  void
     *
     * @dataProvider  requestTokenErrorProvider
     */
    public function testRequestTokenErrors(int $code, string $body, string $message): void
    {
        $this->http->method('post')->willReturn($this->createResponse($code, $body));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($message);

        $this->createHelper()->requestToken('the-code');
    }

    /**
     * @testdox  getAvatarPath() falls back to the default avatar
     *
     * @return  void
     */
    public function testGetAvatarPathDefault(): void
    {
        $user = $this->createUser($this->uniqueUsername());

        $this->assertSame(self::AVATAR_PATH . '/user-default.png', $this->createHelper()->getAvatarPath($user));
    }

    /**
     * @testdox  getAvatarPath() returns the stored avatar of the user
     *
     * @return  void
     */
    public function testGetAvatarPathExisting(): void
    {
        $username = $this->uniqueUsername();
        $path     = self::AVATAR_PATH . '/' . $username . '.png';

        file_put_contents($path, 'png');

        $helper = $this->createHelper();

        $this->assertSame($path, $helper->getAvatarPath($this->createUser($username)));

        // The result is cached for the request
        unlink($path);

        $this->assertSame($path, $helper->getAvatarPath($this->createUser($username)));
    }

    /**
     * @testdox  saveAvatar() does not download an avatar which already exists
     *
     * @return  void
     */
    public function testSaveAvatarExisting(): void
    {
        $username = $this->uniqueUsername();

        file_put_contents(self::AVATAR_PATH . '/' . $username . '.png', 'png');

        $this->githubHttp->expects($this->never())->method('get');
        $this->http->expects($this->never())->method('get');

        $this->assertSame(1, $this->createHelper()->saveAvatar($username));
    }

    /**
     * @testdox  saveAvatar() downloads the avatar from the URL provided by the GitHub API
     *
     * @return  void
     */
    public function testSaveAvatarDownloads(): void
    {
        $username = $this->uniqueUsername();
        $path     = self::AVATAR_PATH . '/' . $username . '.png';

        file_put_contents($path, 'old');

        $this->githubHttp->expects($this->once())
            ->method('get')
            ->with($this->callback(static function ($url) use ($username) {
                return (string) $url === 'https://api.github.com/users/' . $username;
            }))
            ->willReturn($this->createResponse(200, '{"avatar_url":"https://avatars.example.org/u/1"}'));

        $this->http->expects($this->once())
            ->method('get')
            ->with('https://avatars.example.org/u/1')
            ->willReturn($this->createResponse(200, 'new-image'));

        $this->assertSame(9, $this->createHelper()->saveAvatar($username, true));
        $this->assertSame('new-image', file_get_contents($path));
    }

    /**
     * @testdox  saveAvatar() throws when the avatar can not be retrieved
     *
     * @return  void
     */
    public function testSaveAvatarDownloadFails(): void
    {
        $username = $this->uniqueUsername();

        $this->githubHttp->method('get')
            ->willReturn($this->createResponse(200, '{"avatar_url":"https://avatars.example.org/u/1"}'));
        $this->http->method('get')
            ->willReturn($this->createResponse(404, 'Not found'));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Can not retrieve the avatar for user ' . $username);

        try {
            $this->createHelper()->saveAvatar($username);
        } finally {
            $this->assertFileDoesNotExist(self::AVATAR_PATH . '/' . $username . '.png');
        }
    }

    /**
     * @testdox  setLastVisitTime() updates the last visit date of the user
     *
     * @return  void
     */
    public function testSetLastVisitTime(): void
    {
        $queries = [];
        $this->expectQueries($queries);

        $this->db->expects($this->once())->method('execute')->willReturn(true);

        $before = gmdate('Y-m-d H:i:s');
        $this->createHelper()->setLastVisitTime('42');
        $after = gmdate('Y-m-d H:i:s');

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('UPDATE `#__users`', $queries[0]);
        $this->assertStringContainsString('WHERE `id`=42', $queries[0]);
        $this->assertMatchesRegularExpression("/SET `lastvisitDate`='([0-9: -]+)'/", $queries[0]);

        preg_match("/`lastvisitDate`='([0-9: -]+)'/", $queries[0], $matches);

        $this->assertGreaterThanOrEqual($before, $matches[1]);
        $this->assertLessThanOrEqual($after, $matches[1]);
    }

    /**
     * @testdox  refreshUser() refreshes the avatar and stores the user data
     *
     * @return  void
     */
    public function testRefreshUser(): void
    {
        $username = $this->uniqueUsername();
        $path     = self::AVATAR_PATH . '/' . $username . '.png';

        file_put_contents($path, 'old');

        $queries = [];
        $this->expectQueries($queries);

        $this->db->expects($this->once())->method('execute')->willReturn(true);

        $this->githubHttp->method('get')
            ->willReturn($this->createResponse(200, '{"avatar_url":"https://avatars.example.org/u/1"}'));
        $this->http->method('get')
            ->willReturn($this->createResponse(200, 'new-image'));

        $user        = $this->createUser($username);
        $user->id    = 7;
        $user->name  = "O'Brien";
        $user->email = 'obrien@example.org';

        $helper = $this->createHelper();

        $this->assertSame($helper, $helper->refreshUser($user));
        $this->assertSame('new-image', file_get_contents($path));

        $this->assertCount(1, $queries);
        $this->assertStringContainsString('UPDATE `#__users`', $queries[0]);
        $this->assertStringContainsString("`email`='obrien@example.org'", $queries[0]);
        $this->assertStringContainsString("`name`='O\\'Brien'", $queries[0]);
        $this->assertStringContainsString('WHERE `id`=7', $queries[0]);
    }
}
