<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\Hooks;

use App\Projects\TrackerProject;
use App\Tracker\Controller\Hooks\ReceiveStatusHook;
use JTracker\Controller\AjaxResponse;
use JTracker\Github\Package\Repositories\Statuses;
use JTracker\Tests\Mocks\RecordingDatabaseMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for the CI status webhook.
 *
 * @since  1.0
 */
class ReceiveStatusHookTest extends TestCase
{
    use RecordingDatabaseMockTrait;

    /**
     * The head commit of the pull request.
     *
     * @var  string
     */
    private const SHA = '0123456789abcdef0123456789abcdef01234567';

    /**
     * Number of calls to the GitHub status API.
     *
     * @var  integer
     */
    private $apiCalls = 0;

    /**
     * Run the hook with the given payload.
     *
     * @param   object  $hookData  The webhook payload.
     * @param   array   $ids       The ids of the open pull requests with the head commit.
     *
     * @return  AjaxResponse
     */
    private function runHook(object $hookData, array $ids = [7]): AjaxResponse
    {
        $this->addDbResult('/SELECT `id`\s+FROM `#__issues`/', array_map('strval', $ids));

        $api = $this->createStub(Statuses::class);
        $api->method('getCombined')->willReturnCallback(
            function () {
                $this->apiCalls++;

                return (object) ['state' => 'pending', 'statuses' => []];
            }
        );
        $api->method('getCheckRuns')->willReturn(
            (object) [
                'total_count' => 1,
                'check_runs'  => [
                    (object) [
                        'name'       => 'Run Unit tests (PHP 8.3)',
                        'status'     => 'completed',
                        'conclusion' => 'success',
                        'html_url'   => 'https://github.com/joomla/jissues/actions/runs/1/job/2',
                        'output'     => (object) ['title' => null],
                    ],
                ],
            ]
        );

        $db   = $this->createDatabaseMock();
        $hook = (new \ReflectionClass(ReceiveStatusHook::class))->newInstanceWithoutConstructor();

        $properties = [
            'hookData' => $hookData,
            'project'  => new TrackerProject($db, ['project_id' => 1, 'gh_user' => 'joomla', 'gh_project' => 'jissues']),
            'github'   => (object) ['repositories' => (object) ['statuses' => $api]],
            'db'       => $db,
            'response' => new AjaxResponse(),
        ];

        foreach ($properties as $name => $value) {
            (new \ReflectionProperty($hook, $name))->setValue($hook, $value);
        }

        $hook->setLogger(new NullLogger());

        (new \ReflectionMethod($hook, 'prepareResponse'))->invoke($hook);

        return (new \ReflectionProperty($hook, 'response'))->getValue($hook);
    }

    /**
     * Build a check_run payload.
     *
     * @param   string  $action  The action.
     *
     * @return  object
     */
    private static function checkRunPayload(string $action): object
    {
        return (object) [
            'action'     => $action,
            'check_run'  => (object) ['head_sha' => self::SHA, 'status' => 'completed', 'conclusion' => 'success'],
            'repository' => (object) ['name' => 'jissues'],
        ];
    }

    /**
     * Get the UPDATE queries that were executed.
     *
     * @return  string[]
     */
    private function getUpdates(): array
    {
        return array_values(
            array_filter($this->getDbQueries(), static fn ($sql) => str_starts_with(ltrim($sql), 'UPDATE'))
        );
    }

    /**
     * @return  void
     */
    #[TestDox('A completed check run updates the CI status of the pull request with that head commit')]
    public function testCompletedCheckRunUpdatesStatus(): void
    {
        $response = $this->runHook(self::checkRunPayload('completed'));

        $select = $this->getDbQueries()[0];

        $this->assertStringContainsString("`pr_head_sha` = '" . self::SHA . "'", $select);
        $this->assertStringContainsString('`project_id` = 1', $select);
        $this->assertStringContainsString('`closed_date` IS NULL', $select);

        $updates = $this->getUpdates();

        $this->assertCount(1, $updates);
        $this->assertStringContainsString("`merge_state` = 'success'", $updates[0]);
        $this->assertStringContainsString('Run Unit tests (PHP 8.3)', $updates[0]);
        $this->assertStringContainsString('`id` IN (7)', $updates[0]);
        $this->assertSame('Updated the CI status (success) of 1 item(s).', $response->message);
        $this->assertSame('', $response->error);
    }

    /**
     * @return  void
     */
    #[TestDox('A status event updates the CI status')]
    public function testStatusEventUpdatesStatus(): void
    {
        $response = $this->runHook(
            (object) ['sha' => self::SHA, 'state' => 'success', 'context' => 'ci/external', 'repository' => (object) ['name' => 'jissues']],
            [7, 9]
        );

        $updates = $this->getUpdates();

        $this->assertCount(1, $updates);
        $this->assertStringContainsString('`id` IN (7,9)', $updates[0]);
        $this->assertSame('Updated the CI status (success) of 2 item(s).', $response->message);
    }

    /**
     * Data provider for payloads that don't change a status.
     *
     * @return  array
     */
    public static function ignoredPayloadProvider(): array
    {
        return [
            'check run rerequested'      => [self::checkRunPayload('rerequested')],
            'check run requested action' => [self::checkRunPayload('requested_action')],
            'payload without commit'     => [(object) ['action' => 'created', 'repository' => (object) ['name' => 'jissues']]],
            'status with an invalid SHA' => [(object) ['sha' => 'master', 'state' => 'success']],
        ];
    }

    /**
     * @param   object  $hookData  The webhook payload.
     *
     * @return  void
     */
    #[DataProvider('ignoredPayloadProvider')]
    #[TestDox('Ignored: $_dataName')]
    public function testIgnoredPayloads(object $hookData): void
    {
        $response = $this->runHook($hookData);

        $this->assertSame([], $this->getDbQueries());
        $this->assertSame(0, $this->apiCalls);
        $this->assertSame('No status change to process.', $response->message);
    }

    /**
     * @return  void
     */
    #[TestDox('A commit that is not the head of an open pull request is not looked up on GitHub')]
    public function testUnknownCommit(): void
    {
        $response = $this->runHook(self::checkRunPayload('completed'), []);

        $this->assertCount(1, $this->getDbQueries());
        $this->assertSame([], $this->getUpdates());
        $this->assertSame(0, $this->apiCalls);
        $this->assertSame('No open pull request has this head commit.', $response->message);
    }
}
