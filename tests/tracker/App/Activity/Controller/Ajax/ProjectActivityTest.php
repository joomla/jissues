<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Activity\Controller\Ajax;

use App\Activity\Controller\Ajax\ProjectActivity;
use JTracker\Tests\App\Activity\ActivityTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the project activity chart AJAX controller.
 *
 * @since  1.0
 */
class ProjectActivityTest extends ActivityTestCase
{
    /**
     * Data provider for the period input.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function periodProvider(): array
    {
        return [
            'weeks'              => [['period' => '1'], 1],
            'months'             => [['period' => '2'], 2],
            'quarters'           => [['period' => '3'], 3],
            'missing'            => [[], 1],
            'zero'               => [['period' => '0'], 1],
            'year not supported' => [['period' => '4'], 1],
            'custom'             => [['period' => '5'], 1],
            'out of range'       => [['period' => '42'], 1],
            'not a number'       => [['period' => 'weeks'], 1],
        ];
    }

    /**
     * The period is limited to the values supported by the chart.
     *
     * @param   array    $request  The request.
     * @param   integer  $period   The expected period.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('periodProvider')]
    public function testPeriodIsClamped(array $request, int $period): void
    {
        $state = $this->getModelState($this->createController(ProjectActivity::class, $request));

        $this->assertSame($period, $state->get('list.period'));
        $this->assertSame(25, $state->get('list.limit'));
        $this->assertSame(0, $state->get('list.start'));
    }

    /**
     * The opened, fixed and closed counts are converted to chart series.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBuildsSeries(): void
    {
        $this->addDbResult(
            '/fixed1/',
            (object) [
                'fixed1'  => '1', 'fixed2' => '2', 'fixed3' => '3', 'fixed4' => '4',
                'closed1' => '5', 'closed2' => '6', 'closed3' => '7', 'closed4' => '8',
            ]
        );
        $this->addDbResult(
            '/opened1/',
            (object) ['end_date' => '2024-03-31', 'opened1' => '9', 'opened2' => '10', 'opened3' => '11', 'opened4' => '12']
        );

        $response = $this->executeController($this->createController(ProjectActivity::class, ['period' => '9']));

        $this->assertSame('', $response->error);

        [$data, $ticks, $labels, $title] = $response->data;

        $this->assertSame([[12, 11, 10, 9], [4, 3, 2, 1], [8, 7, 6, 5]], $data);
        $this->assertSame(['Opened', 'Fixed', 'Other Closed'], array_column($labels, 'label'));
        $this->assertSame(['04 Mar - 10 Mar', '11 Mar - 17 Mar', '18 Mar - 24 Mar', '25 Mar - 31 Mar'], $ticks);
        $this->assertSame('Issues Opened and Closed for Past Four Weeks', $title);

        // The clamped period results in weekly intervals
        foreach ($this->getDbQueries() as $sql) {
            $this->assertStringContainsString('INTERVAL -28 DAY', $sql);
        }
    }
}
