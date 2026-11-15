<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Hooks\Listeners;

use App\Projects\TrackerProject;
use App\Tracker\Controller\Hooks\Listeners\JoomlacmsPullsListener;
use App\Tracker\Table\IssuesTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\Mysqli\MysqliQuery;
use Joomla\DI\Container;
use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Input\Input;
use Joomla\Registry\Registry;
use JTracker\Application\Application;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for posting pull requests to the testing channel when the promotion label is set.
 *
 * @since  1.0
 */
class JoomlacmsPullsListenerPromotionTest extends TestCase
{
    #[TestDox('Setting the promotion label posts the pull request to the testing channel')]
    public function testPostsWhenPromotionLabelIsSet(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->once())
            ->method('post')
            ->with('https://chat.example.org/hooks/abc', $this->stringContains('Testers wanted: [#48496'))
            ->willReturn(new Response('php://memory', 200));

        $this->promote($http, 'testers WANTED');
    }

    #[TestDox('Other labels are not posted')]
    public function testIgnoresOtherLabels(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->never())->method('post');

        $this->promote($http, 'Bug');
    }

    #[TestDox('Nothing is posted without a configured webhook')]
    public function testIgnoresLabelWithoutWebhook(): void
    {
        $http = $this->createMock(Http::class);
        $http->expects($this->never())->method('post');

        $this->promote($http, 'Testers wanted', '');
    }

    /**
     * Run promoteForTesting() for a "labeled" webhook.
     *
     * @param   Http    $http        The HTTP client.
     * @param   string  $label       The label that was set.
     * @param   string  $webhookUrl  The configured Mattermost webhook.
     *
     * @return  void
     */
    private function promote(Http $http, string $label, string $webhookUrl = 'https://chat.example.org/hooks/abc'): void
    {
        $app = new Application(
            new Session(new MockArraySessionStorage()),
            new Input([]),
            new Registry(['mattermost' => ['webhook_url' => $webhookUrl, 'promote_label' => 'Testers wanted', 'channel' => '']])
        );
        $app->set('uri', (object) ['base' => (object) ['full' => 'https://issues.joomla.org/']]);

        $container = new Container();
        $container->set('app', $app);
        $container->set('http', $http);
        $container->set('db', $this->createDatabase());

        $listener = new JoomlacmsPullsListener();
        $listener->setContainer($container);

        $table = $this->createStub(IssuesTable::class);
        $table->method('__get')->willReturnCallback(static fn ($name) => [
            'id'              => 77,
            'issue_number'    => 48496,
            'title'           => 'Fix the label',
            'description_raw' => "### Testing Instructions\n\n1. Step",
            'pr_head_sha'     => str_repeat('a', 40),
        ][$name] ?? null);

        $project = new TrackerProject(
            $this->createStub(DatabaseDriver::class),
            ['project_id' => 1, 'alias' => 'joomla-cms', 'gh_user' => 'joomla', 'gh_project' => 'joomla-cms']
        );

        $hookData = (object) ['action' => 'labeled', 'label' => (object) ['name' => $label], 'sender' => (object) ['login' => 'maintainer']];

        (new \ReflectionMethod($listener, 'promoteForTesting'))->invoke($listener, $hookData, $this->createStub(Logger::class), $project, $table);
    }

    /**
     * A database stub on which the promotion can be claimed.
     *
     * @return  DatabaseDriver
     */
    private function createDatabase(): DatabaseDriver
    {
        $db = $this->createStub(DatabaseDriver::class);
        $db->method('getQuery')->willReturnCallback(static fn () => new MysqliQuery($db));
        $db->method('quoteName')->willReturnCallback(static fn ($name) => \is_array($name) ? $name : '`' . $name . '`');
        $db->method('quote')->willReturnCallback(static fn ($text) => "'" . addslashes((string) $text) . "'");
        $db->method('setQuery')->willReturnSelf();
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $db->method('getAffectedRows')->willReturn(1);
        $db->method('loadResult')->willReturn(null);

        return $db;
    }
}
