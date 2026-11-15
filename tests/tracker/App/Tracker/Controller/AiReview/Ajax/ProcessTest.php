<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Controller\AiReview\Ajax;

use App\Tracker\Controller\AiReview\Ajax\Process;
use JTracker\Tests\App\Tracker\Controller\AjaxControllerTestTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for dismissing AI review findings and marking them as done.
 *
 * @since  1.0
 */
class ProcessTest extends TestCase
{
    use AjaxControllerTestTrait;

    private const REQUEST = ['project_alias' => 'joomla-cms', 'findingId' => 5, 'state' => 'dismissed'];

    /**
     * The request method before the test.
     *
     * @var    string|null
     * @since  1.0
     */
    private $requestMethod;

    /**
     * Use a POST request unless a test says otherwise.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->requestMethod         = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD']   = 'POST';
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

    #[TestDox('An open finding of the project is dismissed and the admin is recorded')]
    public function testDismissesOpenFinding(): void
    {
        $db = $this->createDatabase(null, (object) ['id' => 5, 'state' => 'open']);
        $db->method('getAffectedRows')->willReturn(1);
        $db->method('getDateFormat')->willReturn('Y-m-d H:i:s');

        $response = $this->executeController(new Process(), $db, self::REQUEST);

        $this->assertSame('', $response->error);
        $this->assertSame('Dismissed.', $response->message);
        $this->assertSame(['setProject:joomla-cms', 'check:admin'], $this->userCalls);

        // Ownership check, freshness check, update
        $this->assertCount(3, $this->issueQueries);
        $this->assertStringContainsString('`r`.`project_id` = 42', $this->issueQueries[0]);
        $this->assertStringContainsString('NOT EXISTS', $this->issueQueries[1]);
        $this->assertStringContainsString("`state` = 'dismissed'", $this->issueQueries[2]);
        $this->assertStringContainsString("`processed_by` = 'tester'", $this->issueQueries[2]);
        $this->assertStringContainsString("`state` = 'open'", $this->issueQueries[2]);
    }

    #[TestDox('A finding of another project or an unknown finding is rejected')]
    public function testRejectsUnknownFinding(): void
    {
        $db = $this->createDatabase(null, null);

        $response = $this->executeController(new Process(), $db, self::REQUEST);

        $this->assertSame('The finding does not exist.', $response->error);
        $this->assertCount(1, $this->issueQueries);
    }

    #[TestDox('A finding that was processed already is not changed again')]
    public function testRejectsProcessedFinding(): void
    {
        $db = $this->createDatabase(null, (object) ['id' => 5, 'state' => 'done']);

        $response = $this->executeController(new Process(), $db, self::REQUEST);

        $this->assertSame('The finding was already processed.', $response->error);
        $this->assertCount(1, $this->issueQueries);
    }

    #[TestDox('Only the states dismissed and done can be set')]
    public function testRejectsOtherStates(): void
    {
        $db = $this->createDatabase(null, (object) ['id' => 5, 'state' => 'open']);

        $response = $this->executeController(new Process(), $db, ['state' => 'accepted'] + self::REQUEST);

        $this->assertSame('Unexpected finding state.', $response->error);
        $this->assertCount(0, $this->issueQueries);
    }

    #[TestDox('Users who are no site admins can not process findings')]
    public function testRejectsNonAdmins(): void
    {
        $this->deniedActions = ['admin'];

        $db = $this->createDatabase(null, (object) ['id' => 5, 'state' => 'open']);

        $response = $this->executeController(new Process(), $db, self::REQUEST);

        $this->assertSame('You are not allowed to process AI review findings.', $response->error);
        $this->assertCount(0, $this->issueQueries);
    }

    #[TestDox('A GET request changes nothing')]
    public function testRejectsGetRequests(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $db = $this->createDatabase(null, (object) ['id' => 5, 'state' => 'open']);

        $response = $this->executeController(new Process(), $db, self::REQUEST);

        $this->assertSame('This action requires a POST request.', $response->error);
        $this->assertCount(0, $this->issueQueries);
    }
}
