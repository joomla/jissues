<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\Comment\Ajax;

use App\Tracker\Controller\Comment\Ajax\Submit;
use JTracker\Tests\App\Tracker\Controller\AjaxControllerTestTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the comment submit AJAX controller.
 *
 * @since  1.0
 */
class SubmitTest extends TestCase
{
    use AjaxControllerTestTrait;

    /**
     * @return  void
     */
    #[TestDox('The issue is looked up in the project from the request, not in the project stored in the session')]
    public function testUsesProjectFromRequest(): void
    {
        $db = $this->createDatabase(null);

        $this->executeController(new Submit(), $db, ['project_alias' => 'joomla-cms', 'issue_number' => 5, 'text' => 'Works for me']);

        $this->assertCount(1, $this->issueQueries);
        $this->assertStringContainsString('`issue_number` = 5', $this->issueQueries[0]);
        $this->assertStringContainsString('`project_id` = 42', $this->issueQueries[0]);
    }

    /**
     * @return  void
     */
    #[TestDox('A comment on an issue number which does not exist in the project from the request is rejected')]
    public function testRejectsIssueOfOtherProject(): void
    {
        $db = $this->createDatabase(null);

        $response = $this->executeController(new Submit(), $db, ['project_alias' => 'joomla-cms', 'issue_number' => 5, 'text' => 'Works for me']);

        $this->assertSame('Invalid issue number.', $response->error);
        $this->assertSame('', $response->message);
    }

    /**
     * @return  void
     */
    #[TestDox('The permissions of the user are checked for the project from the request')]
    public function testChecksPermissionsForProjectFromRequest(): void
    {
        $db = $this->createDatabase(null);

        $this->executeController(new Submit(), $db, ['project_alias' => 'joomla-cms', 'issue_number' => 5, 'text' => 'Works for me']);

        $this->assertSame(['setProject:joomla-cms', 'authorize:create'], $this->userCalls);
    }

    /**
     * @return  void
     */
    #[TestDox('A comment without an issue number is rejected before the issue is looked up')]
    public function testRequiresIssueNumber(): void
    {
        $db = $this->createDatabase(1);

        $response = $this->executeController(new Submit(), $db, ['project_alias' => 'joomla-cms', 'text' => 'Works for me']);

        $this->assertSame('No issue number received.', $response->error);
        $this->assertSame([], $this->issueQueries);
    }
}
