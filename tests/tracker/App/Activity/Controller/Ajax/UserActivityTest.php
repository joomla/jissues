<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Activity\Controller\Ajax;

use App\Activity\Controller\Ajax\UserActivity;
use JTracker\Tests\App\Activity\ActivityTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * Tests for the user activity chart AJAX controller.
 *
 * @since  1.0
 */
class UserActivityTest extends ActivityTestCase
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
            '7 days'               => [['period' => '1'], 1],
            '30 days'              => [['period' => '2'], 2],
            '90 days'              => [['period' => '3'], 3],
            '12 months'            => [['period' => '4'], 4],
            'custom'               => [['period' => '5', 'startdate' => '2024-01-01', 'enddate' => '2024-02-01'], 5],
            'custom invalid dates' => [['period' => '5', 'startdate' => '2024-02-30', 'enddate' => '2024-03-01'], 1],
            'custom reversed'      => [['period' => '5', 'startdate' => '2024-03-01', 'enddate' => '2024-02-01'], 1],
            'missing'              => [[], 1],
            'zero'                 => [['period' => '0'], 1],
            'out of range'         => [['period' => '6'], 1],
            'not a number'         => [['period' => 'all'], 1],
        ];
    }

    /**
     * The period is limited to the supported values.
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
        $state = $this->getModelState($this->createController(UserActivity::class, $request));

        $this->assertSame($period, $state->get('list.period'));

        if ($period === 5) {
            $this->assertSame($request['startdate'], $state->get('list.startdate'));
            $this->assertSame($request['enddate'], $state->get('list.enddate'));
        } else {
            $this->assertNull($state->get('list.startdate'));
            $this->assertNull($state->get('list.enddate'));
        }
    }

    /**
     * Data provider for the activity type input.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function activityTypeProvider(): array
    {
        return [
            'missing'      => [[], 0],
            'all'          => [['activity_type' => '0'], 0],
            'tracker'      => [['activity_type' => '1'], 1],
            'test'         => [['activity_type' => '2'], 2],
            'code'         => [['activity_type' => '3'], 3],
            'out of range' => [['activity_type' => '4'], 0],
            'large'        => [['activity_type' => '255'], 0],
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
     * @since   1.0
     */
    #[DataProvider('activityTypeProvider')]
    public function testActivityTypeIsClamped(array $request, int $activityType): void
    {
        $state = $this->getModelState($this->createController(UserActivity::class, $request));

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
            $this->createController(UserActivity::class, ['period' => '8', 'activity_type' => '6'])
        );

        $this->assertSame('', $response->error);

        [$data, $ticks, $labels, $title] = $response->data;

        $this->assertSame([[], [], []], $data);
        $this->assertSame([], $ticks);
        $this->assertSame('All Points for Past 7 Days', $title);

        // The clamped period results in a valid interval
        $queries = $this->getDbQueries();
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('INTERVAL -7 DAY', $queries[0]);
    }

    /**
     * Users are listed in reverse order and only the selected series is returned.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testBuildsSelectedSeries(): void
    {
        $this->addDbResult(
            '/#__activities/',
            [
                (object) ['name' => 'alice', 'tracker_points' => '10', 'test_points' => '20', 'code_points' => '30'],
                (object) ['name' => 'bob', 'tracker_points' => '1', 'test_points' => '2', 'code_points' => '3'],
            ]
        );

        $response = $this->executeController(
            $this->createController(UserActivity::class, ['period' => '4', 'activity_type' => '3'])
        );

        [$data, $ticks, $labels, $title] = $response->data;

        $this->assertSame([[3, 30]], $data);
        $this->assertSame(['bob', 'alice'], $ticks);
        $this->assertSame(['Code Points'], array_column($labels, 'label'));
        $this->assertSame('Code Points for Past 12 Months', $title);
    }

    /**
     * A custom range is used in the title and the query.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[RequiresPhpExtension('intl')]
    public function testCustomRange(): void
    {
        $response = $this->executeController(
            $this->createController(
                UserActivity::class,
                ['period' => '5', 'activity_type' => '1', 'startdate' => '2024-01-01', 'enddate' => '2024-02-01']
            )
        );

        $this->assertSame('', $response->error);
        $this->assertSame('Tracker Points From 1 January 2024 Through 1 February 2024', $response->data[3]);

        $queries = $this->getDbQueries();
        $this->assertStringContainsString("'2024-01-01'", $queries[0]);
        $this->assertStringContainsString("'2024-02-01'", $queries[0]);
    }
}
