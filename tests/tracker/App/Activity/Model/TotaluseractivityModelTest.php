<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Activity\Model;

use App\Activity\Model\TotaluseractivityModel;
use Joomla\Registry\Registry;
use JTracker\Tests\App\Activity\ActivityTestCase;

/**
 * Tests for the total user activity model.
 *
 * @since  1.0
 */
class TotaluseractivityModelTest extends ActivityTestCase
{
    /**
     * Run getTotalActivity() with the given state and return the generated SQL.
     *
     * @param   array  $state  The model state.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getSql(array $state): string
    {
        $db = $this->createDatabaseMock();

        $model = new TotaluseractivityModel($db);
        $model->setProject($this->createProject($db));
        $model->setState(new Registry($state));

        $this->addDbResult('/#__activities/', [(object) ['activity_group' => 'Tracker']]);

        $this->assertEquals([(object) ['activity_group' => 'Tracker']], $model->getTotalActivity());

        $queries = $this->getDbQueries();
        $this->assertCount(1, $queries);

        return $queries[0];
    }

    /**
     * Data provider for the period.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function periodProvider(): array
    {
        return [
            'weeks'        => [1, 7],
            'months'       => [2, 30],
            'quarters'     => [3, 90],
            'year'         => [4, 7],
            'custom'       => [5, 7],
            'zero'         => [0, 7],
            'not a number' => ['abc', 7],
            'missing'      => [null, 7],
        ];
    }

    /**
     * The four columns cover consecutive intervals of the period length.
     *
     * @param   mixed    $period  The period state.
     * @param   integer  $days    The expected period length in days.
     *
     * @return  void
     *
     * @dataProvider  periodProvider
     * @since   1.0
     */
    public function testPeriodIntervals($period, int $days): void
    {
        $state = $period === null ? [] : ['list' => ['period' => $period]];

        $sql = $this->getSql($state);

        for ($i = 4; $i > 0; $i--) {
            $this->assertStringContainsString(
                'BETWEEN DATE(DATE_ADD(now(), INTERVAL -' . (($i * $days) - 1) . ' DAY))'
                . '  AND DATE(DATE_ADD(now(), INTERVAL -' . (($i - 1) * $days) . ' DAY)) THEN t.activity_points ELSE 0 END) AS p' . $i,
                $sql
            );
        }

        $this->assertStringContainsString('DATE(a.created_date) > DATE(DATE_ADD(now(), INTERVAL -' . ($days * 4) . ' DAY))', $sql);
        $this->assertStringNotContainsString('INTERVAL - DAY', $sql);
        $this->assertStringContainsString('a.project_id = 3', $sql);
        $this->assertStringContainsString('project_id = 3', $sql);
    }

    /**
     * Data provider for the activity type filter.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function activityTypeProvider(): array
    {
        return [
            'all'          => [0, null, false],
            'tracker'      => [1, 'Tracker', false],
            'test'         => [2, 'Test', false],
            'code'         => [3, null, true],
            'out of range' => [4, null, false],
            'negative'     => [-1, null, false],
            'string'       => ['2', 'Test', false],
            'missing'      => [null, null, false],
        ];
    }

    /**
     * The activity type adds the matching filter.
     *
     * @param   mixed        $activityType  The activity type state.
     * @param   string|null  $group         The expected activity group filter.
     * @param   boolean      $having        Whether the code point HAVING clause is expected.
     *
     * @return  void
     *
     * @dataProvider  activityTypeProvider
     * @since   1.0
     */
    public function testActivityTypeFilter($activityType, ?string $group, bool $having): void
    {
        $state = $activityType === null ? [] : ['list' => ['activity_type' => $activityType]];

        $sql = $this->getSql($state);

        if ($group === null) {
            $this->assertStringNotContainsString('t.activity_group = ', $sql);
        } else {
            $this->assertStringContainsString("t.activity_group = '" . $group . "'", $sql);
        }

        if ($having) {
            $this->assertStringContainsString('HAVING COUNT(c.id) > 0', $sql);
        } else {
            $this->assertStringNotContainsString('HAVING', $sql);
        }

        $this->assertStringContainsString('GROUP BY t.activity_group', $sql);
    }
}
