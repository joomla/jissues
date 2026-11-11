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
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for saving an existing item with IssueModel::save().
 *
 * @since  1.0
 */
class IssueModelSaveTest extends TestCase
{
    use RecordingDatabaseMockTrait;

    /**
     * The stored item, as loaded by the table.
     *
     * @var  array
     */
    private const STORED = [
        'id'              => 5,
        'issue_number'    => 48793,
        'project_id'      => 1,
        'title'           => 'Fix accessibility of install extensions from web',
        'description'     => '<p>Description</p>',
        'description_raw' => 'Description',
        'status'          => 1,
        'priority'        => 3,
        'build'           => '6.2-dev',
        'rel_number'      => null,
        'rel_type'        => null,
        'milestone_id'    => null,
        'labels'          => '',
        'has_code'        => 0,
        'commits'         => null,
        'pr_head_sha'     => null,
        'pr_head_user'    => null,
        'pr_head_ref'     => null,
        'modified_date'   => '2026-11-05 22:38:49',
        'modified_by'     => 'tester',
        'opened_date'     => '2026-11-05 22:10:40',
        'opened_by'       => 'author',
        'closed_date'     => null,
        'closed_by'       => null,
    ];

    /**
     * Saves the stored item with the given changes and returns the object written to the database.
     *
     * @param   array  $changes  The changed fields.
     *
     * @return  object
     */
    private function save(array $changes): object
    {
        $this->addDbTableColumns('#__issues', array_keys(self::STORED));
        $this->addDbTableColumns('#__activities', ['activities_id', 'gh_comment_id', 'issue_number', 'project_id', 'user', 'event', 'text', 'text_raw', 'created_date', 'updated_date']);
        $this->addDbResult('/FROM `#__issues`/', self::STORED);

        $model = new IssueModel($this->createDatabaseMock());

        $model->save(
            array_merge(
                self::STORED,
                ['old_state' => 'open', 'new_state' => 'open', 'modified_date' => '2026-11-09 10:00:00'],
                $changes
            )
        );

        $updates = array_values(array_filter($this->dbUpdates, static fn ($update) => $update[0] === '#__issues'));

        $this->assertCount(1, $updates);

        return $updates[0][1];
    }

    /**
     * @return  void
     */
    #[TestDox('Saving an item can mark it as a pull request')]
    public function testSaveStoresHasCode(): void
    {
        $stored = $this->save(['has_code' => 1]);

        $this->assertSame(1, (int) $stored->has_code);
    }

    /**
     * @return  void
     */
    #[TestDox('Saving an item without has_code keeps the stored value')]
    public function testSaveWithoutHasCodeKeepsStoredValue(): void
    {
        $data = self::STORED;
        unset($data['has_code']);

        $this->addDbTableColumns('#__issues', array_keys(self::STORED));
        $this->addDbResult('/FROM `#__issues`/', array_merge(self::STORED, ['has_code' => 1]));

        (new IssueModel($this->createDatabaseMock()))->save(
            array_merge($data, ['old_state' => 'open', 'new_state' => 'open'])
        );

        $updates = array_values(array_filter($this->dbUpdates, static fn ($update) => $update[0] === '#__issues'));

        $this->assertCount(1, $updates);
        $this->assertSame(1, (int) $updates[0][1]->has_code);
    }

    /**
     * @return  void
     */
    #[TestDox('Marking an item as a pull request is not logged as a change in the activity stream')]
    public function testHasCodeChangeIsNotLogged(): void
    {
        $this->save(['has_code' => 1]);

        $this->assertSame(
            [],
            array_values(array_filter($this->dbInserts, static fn ($insert) => $insert[0] === '#__activities'))
        );
    }
}
