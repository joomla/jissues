<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Twig;

use App\Tracker\Twig\MilestoneExtension;
use JTracker\Tests\Mocks\DatabaseMockTrait;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * Tests for the MilestoneExtension class.
 *
 * @since  1.0
 */
class MilestoneExtensionTest extends TestCase
{
    use DatabaseMockTrait;

    /**
     * Milestone rows as returned by the database
     *
     * @var  array
     */
    private const MILESTONES = [
        ['milestone_id' => 3, 'title' => '4.0.0'],
        ['milestone_id' => 7, 'title' => '4.1.0'],
    ];

    /**
     * Creates the extension with a mocked database.
     *
     * @return  MilestoneExtension
     */
    private function createExtension(): MilestoneExtension
    {
        return new MilestoneExtension($this->createDatabaseMock(self::MILESTONES));
    }

    /**
     * @return  void
     */
    #[TestDox('The extension registers the milestone_title function')]
    public function testGetFunctions(): void
    {
        $extension = $this->createExtension();
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertInstanceOf(TwigFunction::class, $functions[0]);
        $this->assertSame('milestone_title', $functions[0]->getName());
        $this->assertSame([$extension, 'getMilestoneTitle'], $functions[0]->getCallable());
    }

    /**
     * @return  void
     */
    #[TestDox('getMilestoneTitle() returns the title of a milestone')]
    public function testGetMilestoneTitle(): void
    {
        $extension = $this->createExtension();

        $this->assertSame('4.0.0', $extension->getMilestoneTitle(3));
        $this->assertSame('4.1.0', $extension->getMilestoneTitle('7'));
    }

    /**
     * @return  void
     */
    #[TestDox('getMilestoneTitle() returns an empty string for an unknown milestone')]
    public function testGetMilestoneTitleUnknown(): void
    {
        $this->assertSame('', $this->createExtension()->getMilestoneTitle(99));
    }

    /**
     * @return  void
     */
    #[TestDox('getMilestoneTitle() loads the milestones from the database only once')]
    public function testGetMilestoneTitleLoadsMilestonesOnce(): void
    {
        $extension = $this->createExtension();

        $extension->getMilestoneTitle(3);
        $extension->getMilestoneTitle(7);
        $extension->getMilestoneTitle(99);

        $this->assertCount(1, $this->executedQueries);
        $this->assertStringContainsString('SELECT `milestone_id`,`title`', $this->executedQueries[0]);
        $this->assertStringContainsString('FROM `#__tracker_milestones`', $this->executedQueries[0]);
    }

    /**
     * @return  void
     */
    #[TestDox('The function can be used in templates')]
    public function testFunctionInTemplate(): void
    {
        $twig = new Environment(new ArrayLoader(['test' => '{{ milestone_title(7) }}|{{ milestone_title(1) }}']));
        $twig->addExtension($this->createExtension());

        $this->assertSame('4.1.0|', $twig->render('test'));
    }
}
