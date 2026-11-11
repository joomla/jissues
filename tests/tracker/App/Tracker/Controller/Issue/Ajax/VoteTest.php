<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\Issue\Ajax;

use App\Tracker\Controller\Issue\Ajax\Vote;
use JTracker\Tests\App\Tracker\Controller\AjaxControllerTestTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the vote AJAX controller.
 *
 * @since  1.0
 */
class VoteTest extends TestCase
{
    use AjaxControllerTestTrait;

    /**
     * The request data of a vote.
     *
     * @var  array
     */
    private const REQUEST = ['project_alias' => 'joomla-cms', 'issueId' => 123, 'experienced' => 1, 'importance' => 3];

    /**
     * @return  void
     */
    #[TestDox('A vote on an issue which does not belong to the project from the request is rejected')]
    public function testRejectsIssueOfOtherProject(): void
    {
        $db = $this->createDatabase(null);

        $response = $this->executeController(new Vote(), $db, self::REQUEST);

        $this->assertSame('Invalid issue ID.', $response->error);

        // Only the ownership check was run, no vote was stored.
        $this->assertCount(1, $this->issueQueries);
        $this->assertStringContainsString('`id` = 123', $this->issueQueries[0]);
        $this->assertStringContainsString('`project_id` = 42', $this->issueQueries[0]);
    }

    /**
     * @return  void
     */
    #[TestDox('A vote on an issue of the project from the request is stored')]
    public function testVotesOnIssueOfProject(): void
    {
        $db = $this->createDatabase(5, (object) ['score' => 6, 'votes' => 2, 'experienced' => 1]);

        $response = $this->executeController(new Vote(), $db, self::REQUEST);

        $this->assertSame('', $response->error);
        $this->assertSame('Vote successfully added', $response->message);
        $this->assertEquals(3, $response->data->importanceScore);
        $this->assertStringContainsString('`project_id` = 42', $this->issueQueries[0]);
    }

    /**
     * @return  void
     */
    #[TestDox('The permissions of the user are checked for the project from the request')]
    public function testChecksPermissionsForProjectFromRequest(): void
    {
        $db = $this->createDatabase(null);

        $this->executeController(new Vote(), $db, self::REQUEST);

        $this->assertSame(['setProject:joomla-cms', 'authorize:view'], $this->userCalls);
    }
}
