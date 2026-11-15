<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Controller\AiReview\Ajax\ReplaceInstructions;
use App\Tracker\Table\AiReviewsTable;
use Joomla\Github\Package\Markdown;
use Joomla\Github\Package\Pulls;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Response;
use JTracker\Github\Github;
use JTracker\Tests\App\Tracker\Controller\AjaxControllerTestTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for replacing the testing instructions of a pull request description.
 *
 * @since  1.0
 */
class ReplaceInstructionsTest extends TestCase
{
    use AjaxControllerTestTrait;

    private const BODY = "### Summary of Changes\n\nFix.\n\n### Testing Instructions\n\nsee issue\n";

    private const REQUEST = ['project_alias' => 'joomla-cms', 'findingId' => 5, 'text' => "### Testing Instructions\n\n1. Step"];

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

    #[TestDox('The sections are replaced on GitHub with the admin\'s account and the finding is accepted')]
    public function testReplacesInstructions(): void
    {
        $pulls = $this->createMock(Pulls::class);
        $pulls->method('get')->willReturn((object) ['body' => self::BODY]);
        $pulls->expects($this->once())
            ->method('edit')
            ->with('joomla', 'joomla-cms', 100, null, "### Summary of Changes\n\nFix.\n\n### Testing Instructions\n\n1. Step\n")
            ->willReturn((object) ['body' => "### Summary of Changes\n\nFix.\n\n### Testing Instructions\n\n1. Step\n"]);

        $db = $this->createDatabase(null, $this->finding());
        $db->method('getAffectedRows')->willReturn(1);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');

        $response = $this->executeController($this->createController($pulls), $db, self::REQUEST);

        $this->assertSame('', $response->error);
        $this->assertSame('The testing instructions in the pull request description were replaced.', $response->message);

        // Load, freshness check, description, review hash, finding
        $this->assertCount(5, $this->issueQueries);
        $this->assertStringContainsString('`description_raw` = ', $this->issueQueries[2]);
        $this->assertStringContainsString('<p>rendered</p>', $this->issueQueries[2]);
        $this->assertStringContainsString(
            AiReviewsTable::hashDescription("### Summary of Changes\n\nFix.\n\n### Testing Instructions\n\n1. Step\n"),
            $this->issueQueries[3]
        );
        $this->assertStringContainsString("`state` = 'accepted'", $this->issueQueries[4]);
        $this->assertStringContainsString('`final_text` = ', $this->issueQueries[4]);
    }

    #[TestDox('Nothing is changed when the description on GitHub changed since the review')]
    public function testRefusesChangedDescription(): void
    {
        $pulls = $this->createMock(Pulls::class);
        $pulls->method('get')->willReturn((object) ['body' => self::BODY . "\nEdited by the author."]);
        $pulls->expects($this->never())->method('edit');

        $db = $this->createDatabase(null, $this->finding());

        $response = $this->executeController($this->createController($pulls), $db, self::REQUEST);

        $this->assertStringStartsWith('The description on GitHub changed since the review.', $response->error);
        $this->assertCount(2, $this->issueQueries);
    }

    #[TestDox('A GitHub error is shown and nothing is stored')]
    public function testShowsGitHubError(): void
    {
        $pulls = $this->createMock(Pulls::class);
        $pulls->method('get')->willReturn((object) ['body' => self::BODY]);
        $pulls->expects($this->once())->method('edit')->willThrowException(
            new UnexpectedResponseException(new Response('php://memory', 403), 'Forbidden')
        );

        $db = $this->createDatabase(null, $this->finding());

        $response = $this->executeController($this->createController($pulls), $db, self::REQUEST);

        $this->assertStringStartsWith('GitHub refused the change with your account: 403', $response->error);
        $this->assertStringEndsWith('Nothing was changed.', $response->error);
        $this->assertCount(2, $this->issueQueries);
    }

    #[TestDox('Only testing instruction findings of pull requests can be adopted')]
    public function testRejectsOtherFindings(): void
    {
        $pulls = $this->createMock(Pulls::class);
        $pulls->expects($this->never())->method('edit');

        $finding       = $this->finding();
        $finding->kind = 'duplicate';

        $response = $this->executeController($this->createController($pulls), $this->createDatabase(null, $finding), self::REQUEST);
        $this->assertSame('This action is not available for this finding.', $response->error);

        $finding           = $this->finding();
        $finding->has_code = 0;

        $response = $this->executeController($this->createController($pulls), $this->createDatabase(null, $finding), self::REQUEST);
        $this->assertSame('Testing instructions can only be replaced in pull requests.', $response->error);
    }

    #[TestDox('Empty instructions are rejected')]
    public function testRejectsEmptyText(): void
    {
        $pulls = $this->createMock(Pulls::class);
        $pulls->expects($this->never())->method('edit');

        $response = $this->executeController(
            $this->createController($pulls),
            $this->createDatabase(null, $this->finding()),
            ['text' => "  \n"] + self::REQUEST
        );

        $this->assertSame('The testing instructions are empty.', $response->error);
    }

    /**
     * An open testing instructions finding of pull request #100.
     *
     * @return  object
     */
    private function finding(): object
    {
        return (object) [
            'id'               => 5,
            'review_id'        => 3,
            'issue_id'         => 77,
            'issue_number'     => 100,
            'kind'             => 'testing_instructions',
            'state'            => 'open',
            'has_code'         => 1,
            'description_hash' => AiReviewsTable::hashDescription(self::BODY),
        ];
    }

    /**
     * The controller with a GitHub client for the given pulls package.
     *
     * @param   Pulls  $pulls  The pulls package.
     *
     * @return  ReplaceInstructions
     */
    private function createController(Pulls $pulls): ReplaceInstructions
    {
        $markdown = $this->createStub(Markdown::class);
        $markdown->method('render')->willReturn('<p>rendered</p>');

        $gitHub = $this->createStub(Github::class);
        $gitHub->method('__get')->willReturnCallback(static fn ($name) => ['pulls' => $pulls, 'markdown' => $markdown][$name] ?? null);

        return new class ($gitHub) extends ReplaceInstructions {
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
