<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Twig;

use App\Tracker\Twig\StatusExtension;
use JTracker\Tests\Mocks\DatabaseMockTrait;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * Tests for the StatusExtension class.
 *
 * @since  1.0
 */
class StatusExtensionTest extends TestCase
{
    use DatabaseMockTrait;

    /**
     * Status rows as stored in the database
     *
     * @var  array
     */
    private const STATUSES = [
        ['id' => 1, 'status' => 'New', 'closed' => 0],
        ['id' => 10, 'status' => 'Closed', 'closed' => 1],
    ];

    /**
     * Creates the extension with a mocked database.
     *
     * @return  StatusExtension
     */
    private function createExtension(): StatusExtension
    {
        return new StatusExtension($this->createDatabaseMock(self::STATUSES));
    }

    /**
     * @testdox  The extension registers the status functions
     *
     * @return  void
     */
    public function testGetFunctions(): void
    {
        $extension = $this->createExtension();
        $functions = [];

        foreach ($extension->getFunctions() as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);

            $functions[$function->getName()] = $function->getCallable();
        }

        $this->assertSame(
            [
                'status'            => [$extension, 'getStatus'],
                'statuses_by_state' => [$extension, 'getStatusesByState'],
            ],
            $functions
        );
    }

    /**
     * @testdox  getStatus() returns the status with a CSS class matching its state
     *
     * @return  void
     */
    public function testGetStatus(): void
    {
        $extension = $this->createExtension();

        $open = $extension->getStatus(1);

        $this->assertSame('New', $open->status);
        $this->assertSame('success', $open->cssClass);

        $closed = $extension->getStatus('10');

        $this->assertSame('Closed', $closed->status);
        $this->assertSame('error', $closed->cssClass);
    }

    /**
     * @testdox  getStatus() loads the statuses from the database only once
     *
     * @return  void
     */
    public function testGetStatusLoadsStatusesOnce(): void
    {
        $extension = $this->createExtension();

        $extension->getStatus(1);
        $extension->getStatus(10);
        $extension->getStatus(1);

        $this->assertCount(1, $this->executedQueries);
        $this->assertStringContainsString('FROM `#__status`', $this->executedQueries[0]);
    }

    /**
     * @testdox  getStatus() throws for an unknown status ID
     *
     * @return  void
     */
    public function testGetStatusUnknown(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown status ID:99');

        $this->createExtension()->getStatus(99);
    }

    /**
     * @testdox  getStatusesByState() returns the open statuses
     *
     * @return  void
     */
    public function testGetStatusesByStateOpen(): void
    {
        $expected = [
            1  => 'New',
            2  => 'Confirmed',
            3  => 'Pending',
            4  => 'Ready To Commit',
            6  => 'Needs Review',
            7  => 'Information Required',
            14 => 'Discussion',
        ];

        $extension = $this->createExtension();

        $this->assertSame($expected, $extension->getStatusesByState(0));
        $this->assertSame($expected, $extension->getStatusesByState('0'));
    }

    /**
     * @testdox  getStatusesByState() returns the closed statuses
     *
     * @return  void
     */
    public function testGetStatusesByStateClosed(): void
    {
        $expected = [
            5  => 'Fixed in Code Base',
            8  => 'Unconfirmed Report',
            9  => 'No Reply',
            10 => 'Closed',
            11 => 'Expected Behaviour',
            12 => 'Known Issue',
            13 => 'Duplicate Report',
        ];

        $extension = $this->createExtension();

        $this->assertSame($expected, $extension->getStatusesByState(1));
        $this->assertSame($expected, $extension->getStatusesByState('1'));
    }

    /**
     * @testdox  getStatusesByState() returns all statuses without a state
     *
     * @return  void
     */
    public function testGetStatusesByStateAll(): void
    {
        $extension = $this->createExtension();

        $all = $extension->getStatusesByState();

        $this->assertSame($all, $extension->getStatusesByState(''));
        $this->assertSame($extension->getStatusesByState(0) + $extension->getStatusesByState(1), $all);
        $this->assertCount(14, $all);
    }

    /**
     * @testdox  The functions can be used in templates
     *
     * @return  void
     */
    public function testFunctionsInTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'test' => '{{ status(10).status }}/{{ status(10).cssClass }}/{{ statuses_by_state(1)|length }}',
        ]));
        $twig->addExtension($this->createExtension());

        $this->assertSame('Closed/error/7', $twig->render('test'));
    }
}
