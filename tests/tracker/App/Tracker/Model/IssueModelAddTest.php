<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Model;

use App\Tracker\Model\IssueModel;
use JTracker\Tests\Mocks\RecordingDatabaseMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for adding a new item with IssueModel::add().
 *
 * @since  1.0
 */
class IssueModelAddTest extends TestCase
{
    use RecordingDatabaseMockTrait;

    /**
     * A new item as the webhooks pass it to the model.
     *
     * @var  array
     */
    private const ITEM = [
        'issue_number'    => 48800,
        'title'           => 'Fix the label filter',
        'description'     => '<p>Description</p>',
        'description_raw' => 'Description',
        'status'          => 1,
        'opened_date'     => '2026-11-11 10:00:00',
        'opened_by'       => 'author',
        'modified_date'   => '2026-11-11 10:00:00',
        'modified_by'     => 'author',
        'project_id'      => 1,
        'build'           => '6.2-dev',
    ];

    /**
     * Adds the item with the given labels and returns the object inserted into the issues table.
     *
     * @param   mixed  $labels  The labels value passed to the model.
     *
     * @return  object
     */
    private function add($labels): object
    {
        $this->addDbTableColumns(
            '#__issues',
            array_merge(['id', 'labels', 'milestone_id', 'rel_type', 'has_code'], array_keys(self::ITEM))
        );
        $this->addDbTableColumns(
            '#__activities',
            ['activities_id', 'gh_comment_id', 'issue_number', 'project_id', 'user', 'event', 'text', 'text_raw', 'created_date', 'updated_date']
        );

        (new IssueModel($this->createDatabaseMock()))->add(array_merge(self::ITEM, ['labels' => $labels]));

        $inserts = array_values(array_filter($this->dbInserts, static fn ($insert) => $insert[0] === '#__issues'));

        $this->assertCount(1, $inserts);

        return $inserts[0][1];
    }

    /**
     * @return  void
     */
    #[TestDox('The label ids of a new item are stored as a comma separated list')]
    public function testLabelIdsAreStoredAsList(): void
    {
        $stored = $this->add([12, '7', 3]);

        $this->assertSame('12,7,3', $stored->labels);
    }

    /**
     * Data provider for labels that are stored without conversion.
     *
     * @return  array
     */
    public static function unconvertedLabelsProvider(): array
    {
        return [
            'no labels'                   => [[], null],
            'already a list'              => ['12,7', '12,7'],
            'GitHub error in the webhook' => ['', ''],
        ];
    }

    /**
     * @param   mixed  $labels    The labels value passed to the model.
     * @param   mixed  $expected  The stored value.
     *
     * @return  void
     */
    #[DataProvider('unconvertedLabelsProvider')]
    #[TestDox('Labels of a new item: $_dataName')]
    public function testOtherLabelValues($labels, $expected): void
    {
        $stored = $this->add($labels);

        $this->assertSame($expected, $stored->labels);
    }
}
