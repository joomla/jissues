<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Command\Get\Project;

use JTracker\Command\Get\Project\Comments;
use JTracker\Github\Package\Issues\Comments as CommentsPackage;
use JTracker\Github\Package\Markdown;
use JTracker\Tests\Command\CommandTestCase;

/**
 * Tests for the get:project_comments command.
 *
 * @since  1.0
 */
class CommentsTest extends CommandTestCase
{
    /**
     * Build a GitHub stub whose comments API returns the given pages per issue.
     *
     * @param   array  $pages  Pages keyed by issue number.
     *
     * @return  array  [GitHub stub, list of requests]
     *
     * @since   1.0
     */
    private function createGitHubWithCommentPages(array $pages): array
    {
        $requested = new \ArrayObject();

        $comments = $this->createMock(CommentsPackage::class);
        $comments->method('getList')
            ->willReturnCallback(
                static function ($owner, $repo, $issue, $page, $limit) use ($pages, $requested) {
                    $requested[] = [$issue, $page, $limit];

                    return $pages[$issue][$page - 1] ?? [];
                }
            );
        $comments->method('getRateLimitRemaining')->willReturn(5000);

        $markdown = $this->createMock(Markdown::class);
        $markdown->method('render')->willReturnCallback(static fn ($text) => '<p>' . $text . '</p>');
        $markdown->method('getRateLimitRemaining')->willReturn(5000);

        $github                   = $this->createGitHubStub();
        $github->issues->comments = $comments;
        $github->markdown         = $markdown;

        return [$github, $requested];
    }

    /**
     * Create a comment object as returned by GitHub.
     *
     * @param   integer  $id    The comment id.
     * @param   string   $body  The body.
     *
     * @return  object
     *
     * @since   1.0
     */
    private function comment(int $id, string $body): object
    {
        return (object) [
            'id'         => $id,
            'body'       => $body,
            'user'       => (object) ['login' => 'alice'],
            'created_at' => '2024-03-01T12:00:00Z',
            'updated_at' => '2024-03-02T12:00:00Z',
        ];
    }

    /**
     * Running with and without progress bar does not throw and stores the new comments of all pages.
     *
     * @param   boolean  $usePBar  Progress bar flag.
     *
     * @return  void
     *
     * @dataProvider  progressBarProvider
     * @since   1.0
     */
    public function testRunsWithAndWithoutProgressBar(bool $usePBar): void
    {
        [$github, $requested] = $this->createGitHubWithCommentPages(
            [
                5 => [[$this->comment(11, 'first')], [$this->comment(12, 'second')]],
            ]
        );

        $command = new Comments();
        $command->setChangedIssueNumbers([5, 6]);
        $this->writeProperty($command, 'usePBar', $usePBar);

        $this->assertSame(0, $this->runCommand($command, ['--project' => 1], $this->createDatabaseMock(), $github));

        $this->assertSame([[5, 1, 100], [5, 2, 100], [5, 3, 100], [6, 1, 100]], $requested->getArrayCopy());

        $this->assertSame([11, 12], array_map(static fn ($insert) => $insert[1]->gh_comment_id, $this->dbInserts));
        $this->assertSame('<p>first</p>', $this->dbInserts[0][1]->text);
        $this->assertSame('first', $this->dbInserts[0][1]->text_raw);

        $display = $this->output->fetch();
        $this->assertStringContainsString('No comments for issue # 6', $display);

        if (!$usePBar) {
            $this->assertStringContainsString('#5 (0/2):', $display);
        }
    }

    /**
     * Data provider for the progress bar flag.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function progressBarProvider(): array
    {
        return [
            'progress bar' => [true],
            'noprogress'   => [false],
        ];
    }
}
