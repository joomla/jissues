<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Controller\AiReview\Ajax\CloseItem;
use Joomla\Github\Package\Issues;
use Joomla\Github\Package\Issues\Comments;
use Joomla\Github\Package\Markdown;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Response;
use JTracker\Github\Github;
use JTracker\Tests\App\Tracker\Controller\AjaxControllerTestTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for confirming a duplicate or a fix elsewhere.
 *
 * @since  1.0
 */
class CloseItemTest extends TestCase
{
    use AjaxControllerTestTrait;

    private const REQUEST = ['project_alias' => 'joomla-cms', 'findingId' => 5, 'text' => 'This is a duplicate of #90.'];

    /**
     * The request method before the test.
     *
     * @var    string|null
     * @since  1.0
     */
    private $requestMethod;

    /**
     * Use a POST request.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMethod       = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    /**
     * Restore the request method.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function tearDown(): void
    {
        if ($this->requestMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->requestMethod;
        }

        parent::tearDown();
    }

    #[TestDox('A confirmed duplicate is commented on and closed, and gets status "Duplicate Report" and the relation')]
    public function testClosesDuplicate(): void
    {
        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())
            ->method('create')
            ->with('joomla', 'joomla-cms', 100, $this->stringStartsWith('This is a duplicate of #90.<hr /><sub>'))
            ->willReturn($this->commentResponse());

        $issues = $this->createIssues($comments);
        $issues->expects($this->once())->method('edit')->with('joomla', 'joomla-cms', 100, 'closed')->willReturn((object) ['state' => 'closed']);

        $db = $this->createDatabase(null, $this->finding('duplicate'));
        $db->method('getAffectedRows')->willReturn(1);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $this->allowActivityStore($db);

        $response = $this->executeController($this->createController($issues), $db, self::REQUEST);

        $this->assertSame('', $response->error);
        $this->assertSame('#100 was commented on and closed.', $response->message);

        // Load, freshness check, comment activity, close, finding
        $this->assertCount(5, $this->issueQueries);
        $this->assertStringContainsString('#__activities', $this->issueQueries[2]);
        $this->assertStringContainsString('`status` = 13', $this->issueQueries[3]);
        $this->assertStringContainsString('`rel_type` = 1', $this->issueQueries[3]);
        $this->assertStringContainsString('`rel_number` = 90', $this->issueQueries[3]);
        $this->assertStringContainsString("`closed_by` = 'tester'", $this->issueQueries[3]);
        $this->assertStringContainsString("`state` = 'accepted'", $this->issueQueries[4]);
    }

    #[TestDox('An item fixed elsewhere gets status "Fixed in Code Base" and a relation to the fixing pull request')]
    public function testClosesFixedElsewhere(): void
    {
        $comments = $this->createStub(Comments::class);
        $comments->method('create')->willReturn($this->commentResponse());

        $issues = $this->createIssues($comments);
        $issues->expects($this->once())->method('edit')->willReturn((object) ['state' => 'closed']);

        $db = $this->createDatabase(null, $this->finding('fixed_elsewhere'));
        $db->method('getAffectedRows')->willReturn(1);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $this->allowActivityStore($db);

        $response = $this->executeController($this->createController($issues), $db, self::REQUEST);

        $this->assertSame('', $response->error);
        $this->assertStringContainsString('`status` = 5', $this->issueQueries[3]);
        $this->assertStringContainsString('`rel_type` = 2', $this->issueQueries[3]);
    }

    #[TestDox('If closing fails after the comment, the admin is told and the finding stays open')]
    public function testCloseFailsAfterComment(): void
    {
        $comments = $this->createStub(Comments::class);
        $comments->method('create')->willReturn($this->commentResponse());

        $issues = $this->createIssues($comments);
        $issues->expects($this->once())->method('edit')->willThrowException(
            new UnexpectedResponseException(new Response('php://memory', 403), 'Forbidden')
        );

        $db = $this->createDatabase(null, $this->finding('duplicate'));
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');
        $this->allowActivityStore($db);

        $response = $this->executeController($this->createController($issues), $db, self::REQUEST);

        $this->assertStringStartsWith('GitHub refused the change with your account: 403', $response->error);
        $this->assertStringEndsWith('The comment was posted, but the item is still open. Close it on GitHub by hand.', $response->error);

        // Load, freshness check, comment activity; no status change, no processed finding
        $this->assertCount(3, $this->issueQueries);
    }

    #[TestDox('Other findings and an empty comment are rejected')]
    public function testRejectsOtherFindingsAndEmptyComment(): void
    {
        $issues = $this->createIssues($this->createStub(Comments::class));
        $issues->expects($this->never())->method('edit');

        $response = $this->executeController($this->createController($issues), $this->createDatabase(null, $this->finding('needs_info')), self::REQUEST);
        $this->assertSame('This action is not available for this finding.', $response->error);

        $response = $this->executeController(
            $this->createController($issues),
            $this->createDatabase(null, $this->finding('duplicate')),
            ['text' => ' '] + self::REQUEST
        );
        $this->assertSame('The comment is empty.', $response->error);
    }

    /**
     * An open finding of item #100 that points to #90.
     *
     * @param   string  $kind  The kind of the finding.
     *
     * @return  object
     */
    private function finding(string $kind): object
    {
        return (object) [
            'id'             => 5,
            'review_id'      => 3,
            'issue_id'       => 77,
            'issue_number'   => 100,
            'kind'           => $kind,
            'state'          => 'open',
            'has_code'       => 0,
            'related_number' => 90,
        ];
    }

    /**
     * A GitHub response for a created comment.
     *
     * @return  object
     */
    private function commentResponse(): object
    {
        return (object) [
            'id'         => 555,
            'created_at' => '2026-11-20T10:00:00Z',
            'user'       => (object) ['login' => 'tester'],
            'body'       => 'This is a duplicate of #90.',
        ];
    }

    /**
     * An issues package mock with the given comments package.
     *
     * @param   Comments  $comments  The comments package.
     *
     * @return  Issues&\PHPUnit\Framework\MockObject\MockObject
     */
    private function createIssues(Comments $comments): Issues
    {
        $issues = $this->createMock(Issues::class);
        $issues->method('__get')->willReturnCallback(static fn ($name) => $name === 'comments' ? $comments : null);

        return $issues;
    }

    /**
     * Let the database stub store the comment activity and record the insert.
     *
     * @param   \Joomla\Database\DatabaseDriver  $db  The database stub.
     *
     * @return  void
     */
    private function allowActivityStore($db): void
    {
        $columns = [];

        foreach (['activities_id', 'gh_comment_id', 'issue_number', 'project_id', 'user', 'event', 'text', 'text_raw', 'created_date', 'updated_date'] as $name) {
            $columns[$name] = (object) ['Field' => $name, 'Default' => null];
        }

        $db->method('getTableColumns')->willReturn($columns);
        $db->method('insertObject')->willReturnCallback(function ($table) {
            $this->issueQueries[] = 'INSERT INTO ' . $table;

            return true;
        });
    }

    /**
     * The controller with a GitHub client for the given issues package.
     *
     * @param   Issues  $issues  The issues package.
     *
     * @return  CloseItem
     */
    private function createController(Issues $issues): CloseItem
    {
        $markdown = $this->createStub(Markdown::class);
        $markdown->method('render')->willReturn('<p>rendered</p>');

        $gitHub = $this->createStub(Github::class);
        $gitHub->method('__get')->willReturnCallback(static fn ($name) => ['issues' => $issues, 'markdown' => $markdown][$name] ?? null);

        return new class ($gitHub) extends CloseItem {
            private $testGitHub;

            public function __construct(Github $gitHub)
            {
                parent::__construct();

                $this->testGitHub = $gitHub;
            }

            protected function getUserGitHub(): Github
            {
                return $this->testGitHub;
            }
        };
    }
}
