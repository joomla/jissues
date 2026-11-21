<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Testing;

use App\Tracker\Testing\TestCommand;
use App\Tracker\Testing\TestCommandParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the `/tested` command parser.
 *
 * @since  1.0
 */
class TestCommandParserTest extends TestCase
{
    /**
     * Data provider for testParse.
     *
     * @return  array
     *
     * @since   1.0
     */
    public static function commentProvider(): array
    {
        return [
            'no command'                => ['I will test this tomorrow.', null, ''],
            'empty comment'             => ['', null, ''],
            'ok'                        => ['/tested ok', TestCommand::SUCCESS, ''],
            'ok with notes on the line' => ['/tested ok works on PHP 8.3', TestCommand::SUCCESS, 'works on PHP 8.3'],
            'ok upper case'             => ['/Tested OK', TestCommand::SUCCESS, ''],
            'success keyword'           => ['/tested success', TestCommand::SUCCESS, ''],
            'pass keyword'              => ['/tested pass', TestCommand::SUCCESS, ''],
            'fail with notes below'     => ["/tested fail\nThe button is missing.\nSecond line.", TestCommand::FAILURE, "The button is missing.\nSecond line."],
            'fail with separator'       => ['/tested fail - the button is missing', TestCommand::FAILURE, 'the button is missing'],
            'failed keyword'            => ['/tested failed', TestCommand::FAILURE, ''],
            'windows line endings'      => ["/tested fail\r\nBroken", TestCommand::FAILURE, 'Broken'],
            'text before the command'   => ["Thanks for the fix!\n\n/tested ok\nAll good.", TestCommand::SUCCESS, 'All good.'],
            'unknown keyword'           => ['/tested maybe', TestCommand::INVALID, ''],
            'missing keyword'           => ['/tested', TestCommand::INVALID, ''],
            'command in a sentence'     => ['Please comment /tested ok after testing.', null, ''],
            'quoted command'            => ["> /tested ok\n\nI agree", null, ''],
            'command in a code block'   => ["```\n/tested ok\n```", null, ''],
            'quoted then own command'   => ["> /tested ok\n/tested fail\nNot for me", TestCommand::FAILURE, 'Not for me'],
            'other command prefix'      => ['/testedok', null, ''],
            'tracker footer is no note' => [
                "/tested ok\r\n\r\nWorks fine\r\n\r\n<!-- jtracker-test-env {\"sha\":\"abc1234\"} --><hr /><sub>This comment was created with the J!Tracker Application</sub>",
                TestCommand::SUCCESS,
                'Works fine',
            ],
        ];
    }

    /**
     * Test parsing of comments.
     *
     * @param   string       $comment  The comment.
     * @param   string|null  $action   The expected action, null if no command is expected.
     * @param   string       $notes    The expected notes.
     *
     * @return  void
     *
     * @since   1.0
     */
    #[DataProvider('commentProvider')]
    public function testParse(string $comment, ?string $action, string $notes): void
    {
        $command = (new TestCommandParser())->parse($comment);

        if ($action === null) {
            $this->assertNull($command);

            return;
        }

        $this->assertNotNull($command);
        $this->assertSame($action, $command->action);
        $this->assertSame($notes, $command->notes);
    }

    /**
     * Test the reset command.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testReset(): void
    {
        $parser = new TestCommandParser();

        $command = $parser->parse('/tested reset @some-user');
        $this->assertSame(TestCommand::RESET, $command->action);
        $this->assertSame('some-user', $command->targetUser);

        $command = $parser->parse('/tested reset');
        $this->assertSame(TestCommand::RESET, $command->action);
        $this->assertNull($command->targetUser);
    }

    /**
     * Test the environment block.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testEnvironment(): void
    {
        $comment = "/tested ok\nWorks.\n<!-- jtracker-test-env {\"joomla\": \"5.3.0\", \"php\": \"8.3.1\", \"sha\": \"ABCDEF1234567\", \"bad key\": 1, \"list\": [1]} -->";

        $command = (new TestCommandParser())->parse($comment);

        $this->assertSame('Works.', $command->notes);
        $this->assertSame(['joomla' => '5.3.0', 'php' => '8.3.1', 'sha' => 'ABCDEF1234567'], $command->environment);
        $this->assertSame('abcdef1234567', $command->getReportedSha());
    }

    /**
     * Test an invalid environment block.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testInvalidEnvironment(): void
    {
        $command = (new TestCommandParser())->parse("/tested ok\n<!-- jtracker-test-env {not json} -->");

        $this->assertSame([], $command->environment);
        $this->assertNull($command->getReportedSha());
    }
}
