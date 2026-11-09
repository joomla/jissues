<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Github;

use Joomla\Github\Package\Authorization;
use Joomla\Github\Package\Issues\Milestones;
use Joomla\Http\Http;
use Joomla\Registry\Registry;
use JTracker\Github\Github;
use JTracker\Github\Package\Issues;
use JTracker\Github\Package\Issues\Labels;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for the lazily created API objects of the tracker's GitHub client.
 *
 * @since  1.0
 */
class GithubTest extends TestCase
{
    /**
     * Deprecation messages triggered during the test.
     *
     * @var  string[]
     */
    private $deprecations = [];

    /**
     * Collects deprecations, such as the creation of dynamic properties.
     *
     * @return  void
     */
    protected function setUp(): void
    {
        parent::setUp();

        set_error_handler(
            function (int $errno, string $message): bool {
                $this->deprecations[] = $message;

                return true;
            },
            E_DEPRECATED | E_USER_DEPRECATED
        );
    }

    /**
     * Restores the error handler.
     *
     * @return  void
     */
    protected function tearDown(): void
    {
        restore_error_handler();

        parent::tearDown();
    }

    /**
     * Creates a client which never connects to GitHub.
     *
     * @return  Github
     */
    private function createGithub(): Github
    {
        return new Github(new Registry(), $this->createStub(Http::class));
    }

    /**
     * @return  void
     */
    #[TestDox('A package of the tracker is created once, gets the logger and creates no dynamic property')]
    public function testTrackerPackage(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $github = $this->createGithub();
        $github->setLogger($logger);

        $issues = $github->issues;

        $this->assertInstanceOf(Issues::class, $issues);
        $this->assertSame($issues, $github->issues);
        $this->assertSame($logger, (new \ReflectionProperty($issues, 'logger'))->getValue($issues));
        $this->assertSame([], $this->deprecations);
    }

    /**
     * @return  void
     */
    #[TestDox('A package of the GitHub library is created once and creates no dynamic property')]
    public function testLibraryPackage(): void
    {
        $github = $this->createGithub();

        $authorization = $github->authorization;

        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertSame($authorization, $github->authorization);
        $this->assertSame([], $this->deprecations);
    }

    /**
     * @return  void
     */
    #[TestDox('Sub-packages of the tracker and of the library are created once and create no dynamic property')]
    public function testSubPackages(): void
    {
        $issues = $this->createGithub()->issues;

        $labels     = $issues->labels;
        $milestones = $issues->milestones;

        $this->assertInstanceOf(Labels::class, $labels);
        $this->assertInstanceOf(Milestones::class, $milestones);
        $this->assertSame($labels, $issues->labels);
        $this->assertSame($milestones, $issues->milestones);
        $this->assertSame([], $this->deprecations);
    }

    /**
     * @return  void
     */
    #[TestDox('An unknown package throws an exception')]
    public function testUnknownPackage(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createGithub()->doesNotExist;
    }
}
