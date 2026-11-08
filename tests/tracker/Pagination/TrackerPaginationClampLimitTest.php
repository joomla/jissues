<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Pagination;

use JTracker\Pagination\TrackerPagination;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TrackerPagination::clampLimit().
 *
 * @since  1.0
 */
class TrackerPaginationClampLimitTest extends TestCase
{
    /**
     * Data provider for testClampLimit.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function limitProvider(): array
    {
        return [
            'zero uses default'           => [0, TrackerPagination::DEFAULT_LIMIT],
            'negative uses default'       => [-5, TrackerPagination::DEFAULT_LIMIT],
            'null uses default'           => [null, TrackerPagination::DEFAULT_LIMIT],
            'non numeric uses default'    => ['abc', TrackerPagination::DEFAULT_LIMIT],
            'minimum is kept'             => [1, 1],
            'regular value is kept'       => [50, 50],
            'maximum is kept'             => [TrackerPagination::MAX_LIMIT, TrackerPagination::MAX_LIMIT],
            'too large is clamped'        => [TrackerPagination::MAX_LIMIT + 1, TrackerPagination::MAX_LIMIT],
            'huge numeric string clamped' => ['99999999999', TrackerPagination::MAX_LIMIT],
        ];
    }

    /**
     * Test that requested limits are clamped to the allowed range.
     *
     * @param   mixed    $limit     The requested limit.
     * @param   integer  $expected  The expected limit.
     *
     * @return  void
     *
     * @dataProvider  limitProvider
     * @since   1.0
     */
    public function testClampLimit($limit, int $expected): void
    {
        $this->assertSame($expected, TrackerPagination::clampLimit($limit));
    }

    /**
     * Test that a custom default is used for invalid limits.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testClampLimitUsesCustomDefault(): void
    {
        $this->assertSame(25, TrackerPagination::clampLimit(0, 25));
    }
}
