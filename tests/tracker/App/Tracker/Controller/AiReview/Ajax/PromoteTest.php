<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Controller\AiReview\Ajax\Promote;
use Joomla\Github\Package\Issues;
use Joomla\Github\Package\Issues\Labels;
use JTracker\Github\Github;
use JTracker\Tests\App\Tracker\Controller\AjaxControllerTestTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for promoting a pull request for testing.
 *
 * @since  1.0
 */
class PromoteTest extends TestCase
{
    use AjaxControllerTestTrait;

    private const REQUEST = ['project_alias' => 'joomla-cms', 'findingId' => 5];

    /**
     * The request method before the test.
     *
     * @var    string|null
     * @since  1.0
     */
    private $requestMethod;

    /**
     * Use a POST request and configure the promotion label.
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
        $this->appConfig           = ['mattermost' => ['promote_label' => 'Testers wanted']];
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

    #[TestDox('The configured label is set on GitHub with the admin\'s account and the finding is accepted')]
    public function testSetsLabel(): void
    {
        $labels = $this->createMock(Labels::class);
        $labels->expects($this->once())->method('add')->with('joomla', 'joomla-cms', 100, ['Testers wanted']);

        $db = $this->createDatabase(null, $this->finding());
        $db->method('getAffectedRows')->willReturn(1);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');

        $response = $this->executeController($this->createController($labels), $db, self::REQUEST);

        $this->assertSame('', $response->error);
        $this->assertStringStartsWith('The label "Testers wanted" was set.', $response->message);
        $this->assertStringContainsString("`state` = 'accepted'", $this->issueQueries[2]);
        $this->assertStringContainsString("`final_text` = 'Testers wanted'", $this->issueQueries[2]);
    }

    #[TestDox('Nothing is set without a configured label')]
    public function testRequiresConfiguredLabel(): void
    {
        $this->appConfig = [];

        $labels = $this->createMock(Labels::class);
        $labels->expects($this->never())->method('add');

        $response = $this->executeController($this->createController($labels), $this->createDatabase(null, $this->finding()), self::REQUEST);

        $this->assertSame('No promotion label is configured (mattermost.promote_label).', $response->error);
    }

    #[TestDox('Only test suitability findings of pull requests can be promoted')]
    public function testRejectsIssuesAndOtherFindings(): void
    {
        $labels = $this->createMock(Labels::class);
        $labels->expects($this->never())->method('add');

        $finding           = $this->finding();
        $finding->has_code = 0;

        $response = $this->executeController($this->createController($labels), $this->createDatabase(null, $finding), self::REQUEST);
        $this->assertSame('Only pull requests can be promoted for testing.', $response->error);

        $finding       = $this->finding();
        $finding->kind = 'duplicate';

        $response = $this->executeController($this->createController($labels), $this->createDatabase(null, $finding), self::REQUEST);
        $this->assertSame('This action is not available for this finding.', $response->error);
    }

    /**
     * An open test suitability finding of pull request #100.
     *
     * @return  object
     */
    private function finding(): object
    {
        return (object) [
            'id'           => 5,
            'review_id'    => 3,
            'issue_id'     => 77,
            'issue_number' => 100,
            'kind'         => 'test_suitability',
            'rating'       => 'yes',
            'state'        => 'open',
            'has_code'     => 1,
        ];
    }

    /**
     * The controller with a GitHub client for the given labels package.
     *
     * @param   Labels  $labels  The labels package.
     *
     * @return  Promote
     */
    private function createController(Labels $labels): Promote
    {
        $issues = $this->createStub(Issues::class);
        $issues->method('__get')->willReturnCallback(static fn ($name) => $name === 'labels' ? $labels : null);

        $gitHub = $this->createStub(Github::class);
        $gitHub->method('__get')->willReturnCallback(static fn ($name) => $name === 'issues' ? $issues : null);

        return new class ($gitHub) extends Promote {
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
