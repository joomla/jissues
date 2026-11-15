<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\Hooks;

use App\Tracker\Controller\Hooks\ReceivePullsHook;
use JTracker\Controller\AjaxResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the routing of pull request webhooks.
 *
 * @since  1.0
 */
class ReceivePullsHookTest extends TestCase
{
    /**
     * Data provider for the label actions.
     *
     * @return  array
     */
    public static function labelActionProvider(): array
    {
        return [
            'labeled'   => ['labeled'],
            'unlabeled' => ['unlabeled'],
        ];
    }

    /**
     * @param   string  $action  The webhook action.
     *
     * @return  void
     */
    #[DataProvider('labelActionProvider')]
    #[TestDox('A $action event of a known pull request updates it')]
    public function testLabelEventUpdatesKnownPullRequest(string $action): void
    {
        $hook = $this->createHook($action, true);

        $this->prepareResponse($hook);

        $this->assertSame(['update'], $hook->calls);
    }

    /**
     * @param   string  $action  The webhook action.
     *
     * @return  void
     */
    #[DataProvider('labelActionProvider')]
    #[TestDox('A $action event of an unknown pull request does not insert it')]
    public function testLabelEventIgnoresUnknownPullRequest(string $action): void
    {
        $hook = $this->createHook($action, false);

        $response = $this->prepareResponse($hook);

        $this->assertSame([], $hook->calls);
        $this->assertSame('The pull request is not in the tracker yet, the label event is ignored.', $response->message);
    }

    #[TestDox('An opened event of an unknown pull request inserts it')]
    public function testOpenedEventInsertsUnknownPullRequest(): void
    {
        $hook = $this->createHook('opened', false);

        $this->prepareResponse($hook);

        $this->assertSame(['insert'], $hook->calls);
    }

    /**
     * Run prepareResponse() and return the response.
     *
     * @param   ReceivePullsHook  $hook  The hook.
     *
     * @return  AjaxResponse
     */
    private function prepareResponse(ReceivePullsHook $hook): AjaxResponse
    {
        (new \ReflectionMethod($hook, 'prepareResponse'))->invoke($hook);

        return (new \ReflectionProperty($hook, 'response'))->getValue($hook);
    }

    /**
     * A hook which records whether it inserts or updates the pull request.
     *
     * @param   string   $action  The webhook action.
     * @param   boolean  $exists  Whether the pull request is in the tracker.
     *
     * @return  ReceivePullsHook
     */
    private function createHook(string $action, bool $exists): ReceivePullsHook
    {
        $class = new class () extends ReceivePullsHook {
            public $calls = [];

            public $exists = false;

            public function __construct()
            {
            }

            protected function checkIssueExists($issue)
            {
                return $this->exists;
            }

            protected function insertData()
            {
                $this->calls[] = 'insert';
            }

            protected function updateData()
            {
                $this->calls[] = 'update';
            }
        };

        $hook         = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        $hook->exists = $exists;

        (new \ReflectionProperty($hook, 'response'))->setValue($hook, new AjaxResponse());
        (new \ReflectionProperty($hook, 'hookData'))->setValue(
            $hook,
            (object) ['action' => $action, 'pull_request' => (object) ['number' => 48496]]
        );

        return $hook;
    }
}
