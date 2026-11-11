<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\Hooks;

use App\Tracker\Controller\Hooks\ReceiveCommentsHook;
use Joomla\Github\Package\Markdown;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the item data the comments hook stores when a comment arrives for an unknown item.
 *
 * @since  1.0
 */
class ReceiveCommentsHookTest extends TestCase
{
    /**
     * Creates the hook with the given payload, without running the controller's constructor.
     *
     * @param   object  $hookData  The webhook payload.
     *
     * @return  ReceiveCommentsHook
     */
    private function createHook(object $hookData): ReceiveCommentsHook
    {
        $markdown = $this->createStub(Markdown::class);
        $markdown->method('render')->willReturnCallback(static fn ($text) => '<p>' . $text . '</p>');

        $hook = (new \ReflectionClass(ReceiveCommentsHook::class))->newInstanceWithoutConstructor();

        $properties = [
            'hookData' => $hookData,
            'project'  => (object) ['project_id' => 1, 'gh_user' => 'joomla', 'gh_project' => 'joomla-cms'],
            'github'   => (object) ['markdown' => $markdown],
        ];

        foreach ($properties as $name => $value) {
            (new \ReflectionProperty($hook, $name))->setValue($hook, $value);
        }

        return $hook;
    }

    /**
     * Builds a comment webhook payload.
     *
     * @param   array  $issue  Properties of the issue data to set or override.
     *
     * @return  object
     */
    private function buildHookData(array $issue = []): object
    {
        return (object) [
            'action' => 'created',
            'issue'  => (object) array_merge(
                [
                    'number'     => 48793,
                    'title'      => 'Fix accessibility of install extensions from web',
                    'body'       => 'Pull request description',
                    'state'      => 'open',
                    'created_at' => '2026-11-05T22:10:40Z',
                    'updated_at' => '2026-11-05T22:38:49Z',
                    'closed_at'  => null,
                    'user'       => (object) ['login' => 'author'],
                ],
                $issue
            ),
            'comment'    => (object) ['id' => 123, 'body' => 'Tested successfully'],
            'sender'     => (object) ['login' => 'tester'],
            'repository' => (object) ['default_branch' => '6.2-dev'],
        ];
    }

    /**
     * Calls the protected getIssueData() method.
     *
     * @param   ReceiveCommentsHook  $hook  The hook.
     *
     * @return  array
     */
    private function getIssueData(ReceiveCommentsHook $hook): array
    {
        return (new \ReflectionMethod($hook, 'getIssueData'))->invoke($hook, 'Y-m-d H:i:s');
    }

    /**
     * @return  void
     */
    #[TestDox('A comment on an unknown pull request stores the item as a pull request')]
    public function testPullRequestIsStoredWithCode(): void
    {
        $hookData = $this->buildHookData(
            ['pull_request' => (object) ['url' => 'https://api.github.com/repos/joomla/joomla-cms/pulls/48793']]
        );

        $data = $this->getIssueData($this->createHook($hookData));

        $this->assertSame(1, $data['has_code']);
        $this->assertSame(48793, $data['issue_number']);
        $this->assertSame('author', $data['opened_by']);
        $this->assertSame('tester', $data['modified_by']);
        $this->assertSame('<p>Pull request description</p>', $data['description']);
    }

    /**
     * @return  void
     */
    #[TestDox('A comment on an unknown issue stores the item without code')]
    public function testIssueIsStoredWithoutCode(): void
    {
        $data = $this->getIssueData($this->createHook($this->buildHookData()));

        $this->assertSame(0, $data['has_code']);
    }

    /**
     * @return  void
     */
    #[TestDox('The comment payload has no pull request head data, so none is stored')]
    public function testNoPullRequestHeadData(): void
    {
        $hookData = $this->buildHookData(['pull_request' => (object) []]);

        $data = $this->getIssueData($this->createHook($hookData));

        $this->assertArrayNotHasKey('pr_head_user', $data);
        $this->assertArrayNotHasKey('pr_head_ref', $data);
        $this->assertArrayNotHasKey('pr_head_sha', $data);
    }

    /**
     * @return  void
     */
    #[TestDox('A closed item gets its closed date and closing user')]
    public function testClosedItem(): void
    {
        $hookData = $this->buildHookData(['state' => 'closed', 'closed_at' => '2026-11-07T10:00:00Z']);

        $data = $this->getIssueData($this->createHook($hookData));

        $this->assertSame(10, $data['status']);
        $this->assertSame('2026-11-07 10:00:00', $data['closed_date']);
        $this->assertSame('tester', $data['closed_by']);
    }
}
