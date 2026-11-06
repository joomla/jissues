<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Activity\Controller\Ajax;

use App\Activity\Controller\Ajax\TotalActivity;
use JTracker\Tests\App\Activity\ActivityTestCase;

/**
 * Tests for the total activity chart AJAX controller.
 *
 * @since  1.0
 */
class TotalActivityTest extends ActivityTestCase
{
    /**
     * Data provider for the period input.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function periodProvider(): array
    {
        return [
            'weeks'                => [['period' => '1'], 1],
            'months'               => [['period' => '2'], 2],
            'quarters'             => [['period' => '3'], 3],
            'missing'              => [[], 1],
            'zero'                 => [['period' => '0'], 1],
            'year not supported'   => [['period' => '4'], 1],
            'out of range'         => [['period' => '99'], 1],
            'negative'             => [['period' => '-2'], 2],
            'not a number'         => [['period' => 'abc'], 1],
            'custom not supported' => [['period' => '5', 'startdate' => '2024-01-01', 'enddate' => '2024-02-01'], 1],
            'custom invalid dates' => [['period' => '5', 'startdate' => '2024-13-01', 'enddate' => 'x'], 1],
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
     * @dataProvider  periodProvider
     * @since   1.0
     */
    public function testPeriodIsClamped(array $request, int $period): void
    {
        $state = $this->getModelState($this->createController(TotalActivity::class, $request));

        $this->assertSame($period, $state->get('list.period'));
    }

    /**
     * Valid custom dates are passed on to the state, invalid ones are dropped.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testCustomDates(): void
    {
        $state = $this->getModelState(
            $this->createController(TotalActivity::class, ['period' => '5', 'startdate' => '2024-01-01', 'enddate' => '2024-02-01'])
        );

        $this->assertSame('2024-01-01', $state->get('list.startdate'));
        $this->assertSame('2024-02-01', $state->get('list.enddate'));

        $state = $this->getModelState(
            $this->createController(TotalActivity::class, ['period' => '5', 'startdate' => '2024-02-01', 'enddate' => '2024-01-01'])
        );

        $this->assertNull($state->get('list.startdate'));
        $this->assertNull($state->get('list.enddate'));
    }

    /**
     * Data provider for the activity type input.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function activityTypeProvider(): array
    {
        return [
            'missing'      => [[], 0],
            'all'          => [['activity_type' => '0'], 0],
            'tracker'      => [['activity_type' => '1'], 1],
            'test'         => [['activity_type' => '2'], 2],
            'code'         => [['activity_type' => '3'], 3],
            'out of range' => [['activity_type' => '4'], 0],
            'large'        => [['activity_type' => '1000'], 0],
            'not a number' => [['activity_type' => 'code'], 0],
        ];
    }

    /**
     * The activity type is limited to the supported types.
     *
     * @param   array    $request       The request.
     * @param   integer  $activityType  The expected type.
     *
     * @return  void
     *
     * @dataProvider  activityTypeProvider
     * @since   1.0
     */
    public function testActivityTypeIsClamped(array $request, int $activityType): void
    {
        $state = $this->getModelState($this->createController(TotalActivity::class, $request));

        $this->assertSame($activityType, $state->get('list.activity_type'));
    }

    /**
     * Unsupported input produces a valid chart instead of an error.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testUnsupportedInputRendersDefaultChart(): void
    {
        $response = $this->executeController(
            $this->createController(TotalActivity::class, ['period' => '7', 'activity_type' => '9'])
        );

        $this->assertSame('', $response->error);
        $this->assertSame('All Points for Past Four Weeks', $response->data[3]);
    }

    /**
     * An empty result renders an empty chart for the current date.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testEmptyResult(): void
    {
        $response = $this->executeController($this->createController(TotalActivity::class, ['period' => '2']));

        $this->assertSame('', $response->error);

        [$data, $ticks, $labels, $title] = $response->data;

        $this->assertSame([], $data);
        $this->assertSame([], $labels);
        $this->assertSame('All Points for Past Four Months', $title);
        $this->assertCount(4, $ticks);
        $this->assertStringEndsWith(' - ' . date('d M'), $ticks[3]);
    }

    /**
     * Rows are converted to chart series in chronological order.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBuildsSeriesFromRows(): void
    {
        $this->addDbResult(
            '/#__activities/',
            [
                (object) ['activity_group' => 'Tracker', 'end_date' => '2024-03-31', 'p1' => '1', 'p2' => '2', 'p3' => '3', 'p4' => '4'],
                (object) ['activity_group' => 'Test', 'end_date' => '2024-03-31', 'p1' => '5', 'p2' => '6', 'p3' => '7', 'p4' => '8'],
            ]
        );

        $response = $this->executeController($this->createController(TotalActivity::class, ['period' => '1']));

        $this->assertSame('', $response->error);

        [$data, $ticks, $labels, $title] = $response->data;

        $this->assertSame([[4, 3, 2, 1], [8, 7, 6, 5]], $data);
        $this->assertSame(['Tracker Points', 'Test Points'], array_column($labels, 'label'));
        $this->assertSame(['04 Mar - 10 Mar', '11 Mar - 17 Mar', '18 Mar - 24 Mar', '25 Mar - 31 Mar'], $ticks);
        $this->assertSame('All Points for Past Four Weeks', $title);
    }

    /**
     * A single activity type only shows one series.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testSingleActivityTypeShowsOneSeries(): void
    {
        $this->addDbResult(
            '/#__activities/',
            [(object) ['activity_group' => 'Test', 'end_date' => '2024-03-31', 'p1' => '5', 'p2' => '6', 'p3' => '7', 'p4' => '8']]
        );

        $response = $this->executeController(
            $this->createController(TotalActivity::class, ['period' => '3', 'activity_type' => '2'])
        );

        [$data, $ticks, $labels, $title] = $response->data;

        $this->assertSame([[8, 7, 6, 5]], $data);
        $this->assertSame(['Test Points'], array_column($labels, 'label'));
        $this->assertSame('Test Points for Past Four Quarters', $title);
        $this->assertSame('02 Jan - 31 Mar', $ticks[3]);
    }
}
