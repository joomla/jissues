<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Tests\App\Tracker\Helper;

use App\Tracker\Helper\IssueChangesHelper;
use Joomla\Github\Github;
use Joomla\Github\Package\Issues;
use Joomla\Github\Package\Issues\Labels;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the IssueChangesHelper.
 *
 * @since  1.0
 */
class IssueChangesHelperTest extends TestCase
{
    /**
     * Project labels, label ID => label object.
     *
     * @return  array
     *
     * @since   1.0
     */
    private static function projectLabels(): array
    {
        return [
            1 => (object) ['name' => 'Bug'],
            2 => (object) ['name' => 'Feature'],
            3 => (object) ['name' => 'Documentation Required'],
        ];
    }

    /**
     * Project milestones.
     *
     * @return  array
     *
     * @since   1.0
     */
    private static function projectMilestones(): array
    {
        return [
            (object) ['milestone_id' => 10, 'milestone_number' => 101],
            (object) ['milestone_id' => 11, 'milestone_number' => 102],
        ];
    }

    /**
     * Data provider for testGetLabelChanges.
     *
     * @return  \Generator
     *
     * @since   1.0
     */
    public static function labelChangesProvider(): \Generator
    {
        yield 'unchanged' => [['1', '2'], ['2', '1'], [], []];
        yield 'label added' => [['1'], ['1', '3'], ['Documentation Required'], []];
        yield 'label removed' => [['1', '2'], ['1'], [], ['Feature']];
        yield 'added and removed' => [['1'], ['2'], ['Feature'], ['Bug']];
        yield 'all removed' => [['1', '2'], [], [], ['Bug', 'Feature']];
        yield 'nothing stored' => [[], ['1'], ['Bug'], []];
        yield 'unknown label IDs are ignored' => [['1', '99'], ['1', '42'], [], []];
        yield 'empty values are ignored' => [[''], ['0', ''], [], []];
    }

    /**
     * @param   array  $stored          Stored label IDs.
     * @param   array  $submitted       Submitted label IDs.
     * @param   array  $expectedAdd     Expected label names to add.
     * @param   array  $expectedRemove  Expected label names to remove.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('labelChangesProvider')]
    #[TestDox('Only the labels changed by the user are added or removed')]
    public function testGetLabelChanges(array $stored, array $submitted, array $expectedAdd, array $expectedRemove): void
    {
        $changes = IssueChangesHelper::getLabelChanges($stored, $submitted, self::projectLabels());

        $this->assertSame($expectedAdd, $changes['add']);
        $this->assertSame($expectedRemove, $changes['remove']);
    }

    /**
     * Data provider for testGetMilestoneChange.
     *
     * @return  \Generator
     *
     * @since   1.0
     */
    public static function milestoneChangeProvider(): \Generator
    {
        yield 'unchanged' => ['10', '10', null];
        yield 'unchanged, none' => [null, '0', null];
        yield 'unchanged, none and missing' => [null, null, null];
        yield 'set' => [null, '11', 102];
        yield 'changed' => ['10', '11', 102];
        yield 'removed' => ['10', '0', ''];
        yield 'unknown milestone' => ['10', '99', null];
    }

    /**
     * @param   mixed                $stored     Stored milestone ID.
     * @param   mixed                $submitted  Submitted milestone ID.
     * @param   integer|string|null  $expected   Expected milestone change.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('milestoneChangeProvider')]
    #[TestDox('The milestone is only changed if the user changed it')]
    public function testGetMilestoneChange($stored, $submitted, int|string|null $expected): void
    {
        $this->assertSame($expected, IssueChangesHelper::getMilestoneChange($stored, $submitted, self::projectMilestones()));
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('A milestone change is detected as applied or dropped from the GitHub response')]
    public function testIsMilestoneApplied(): void
    {
        $withMilestone    = (object) ['milestone' => (object) ['number' => 102]];
        $withoutMilestone = (object) ['milestone' => null];

        $this->assertTrue(IssueChangesHelper::isMilestoneApplied(102, $withMilestone));
        $this->assertFalse(IssueChangesHelper::isMilestoneApplied(101, $withMilestone));
        $this->assertFalse(IssueChangesHelper::isMilestoneApplied(102, $withoutMilestone));
        $this->assertTrue(IssueChangesHelper::isMilestoneApplied('', $withoutMilestone));
        $this->assertFalse(IssueChangesHelper::isMilestoneApplied('', $withMilestone));
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('Labels are added and removed one by one, never replaced')]
    public function testApplyLabelChangesAddsAndRemoves(): void
    {
        $labels = $this->createMock(Labels::class);
        $labels->expects($this->once())
            ->method('add')
            ->with('joomla', 'joomla-cms', 42, ['Feature'])
            ->willReturn([(object) ['name' => 'Bug'], (object) ['name' => 'Feature'], (object) ['name' => 'Unknown to the tracker']]);
        $labels->expects($this->once())
            ->method('removeFromIssue')
            ->with('joomla', 'joomla-cms', 42, 'Bug')
            ->willReturn([(object) ['name' => 'Feature'], (object) ['name' => 'Unknown to the tracker']]);
        $labels->expects($this->never())->method('replace');
        $labels->expects($this->never())->method('removeAllFromIssue');

        IssueChangesHelper::applyLabelChanges([$this->createGitHub($labels)], 'joomla', 'joomla-cms', 42, ['Feature'], ['Bug']);
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('Nothing is sent to GitHub when the labels were not changed')]
    public function testApplyLabelChangesWithoutChanges(): void
    {
        $labels = $this->createMock(Labels::class);
        $labels->expects($this->never())->method('add');
        $labels->expects($this->never())->method('removeFromIssue');

        IssueChangesHelper::applyLabelChanges([$this->createGitHub($labels)], 'joomla', 'joomla-cms', 42, [], []);
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('The bot adds the labels when GitHub denies the user')]
    public function testApplyLabelChangesFallsBackToBotOnPermissionError(): void
    {
        $userLabels = $this->createMock(Labels::class);
        $userLabels->expects($this->once())
            ->method('add')
            ->willThrowException($this->createResponseException(403));

        $botLabels = $this->createMock(Labels::class);
        $botLabels->expects($this->once())
            ->method('add')
            ->with('joomla', 'joomla-cms', 42, ['Bug'])
            ->willReturn([(object) ['name' => 'Bug']]);

        IssueChangesHelper::applyLabelChanges(
            [$this->createGitHub($userLabels), $this->createGitHub($botLabels)],
            'joomla',
            'joomla-cms',
            42,
            ['Bug'],
            []
        );
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('The bot adds the labels when GitHub silently drops them for the user')]
    public function testApplyLabelChangesFallsBackToBotWhenDropped(): void
    {
        $userLabels = $this->createMock(Labels::class);
        $userLabels->expects($this->once())
            ->method('add')
            ->willReturn([]);

        $botLabels = $this->createMock(Labels::class);
        $botLabels->expects($this->once())
            ->method('add')
            ->willReturn([(object) ['name' => 'Bug']]);

        IssueChangesHelper::applyLabelChanges(
            [$this->createGitHub($userLabels), $this->createGitHub($botLabels)],
            'joomla',
            'joomla-cms',
            42,
            ['Bug'],
            []
        );
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('Removing a label which is no longer set on GitHub is not an error')]
    public function testApplyLabelChangesIgnoresMissingLabelOnRemove(): void
    {
        $userLabels = $this->createMock(Labels::class);
        $userLabels->expects($this->once())
            ->method('removeFromIssue')
            ->willThrowException($this->createResponseException(404));

        $botLabels = $this->createMock(Labels::class);
        $botLabels->expects($this->never())->method('removeFromIssue');

        IssueChangesHelper::applyLabelChanges(
            [$this->createGitHub($userLabels), $this->createGitHub($botLabels)],
            'joomla',
            'joomla-cms',
            42,
            [],
            ['Bug']
        );
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('A permission error is thrown when no one may change the labels')]
    public function testApplyLabelChangesThrowsWhenAllFail(): void
    {
        $labels = $this->createStub(Labels::class);
        $labels->method('add')
            ->willThrowException($this->createResponseException(403));

        $this->expectException(UnexpectedResponseException::class);

        IssueChangesHelper::applyLabelChanges([$this->createGitHub($labels)], 'joomla', 'joomla-cms', 42, ['Bug'], []);
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('Other GitHub errors are not swallowed by the bot fallback')]
    public function testApplyLabelChangesRethrowsOtherErrors(): void
    {
        $userLabels = $this->createStub(Labels::class);
        $userLabels->method('add')
            ->willThrowException($this->createResponseException(500));

        $botLabels = $this->createMock(Labels::class);
        $botLabels->expects($this->never())->method('add');

        $this->expectException(UnexpectedResponseException::class);

        IssueChangesHelper::applyLabelChanges(
            [$this->createGitHub($userLabels), $this->createGitHub($botLabels)],
            'joomla',
            'joomla-cms',
            42,
            ['Bug'],
            []
        );
    }

    /**
     * Create a GitHub stub returning the given labels package.
     *
     * @param   Labels  $labels  The labels package.
     *
     * @return  Github
     *
     * @since   1.0
     */
    private function createGitHub(Labels $labels): Github
    {
        $issues = $this->createStub(Issues::class);
        $issues->method('__get')
            ->willReturnMap([['labels', $labels]]);

        $gitHub = $this->createStub(Github::class);
        $gitHub->method('__get')
            ->willReturnMap([['issues', $issues]]);

        return $gitHub;
    }

    /**
     * Create an exception for an unexpected GitHub response.
     *
     * @param   integer  $code  The HTTP status code.
     *
     * @return  UnexpectedResponseException
     *
     * @since   1.0
     */
    private function createResponseException(int $code): UnexpectedResponseException
    {
        return new UnexpectedResponseException(new Response('php://memory', $code), 'Error', $code);
    }
}
