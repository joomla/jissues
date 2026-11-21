<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * Parser for `/tested` commands in GitHub comments.
 *
 * Grammar (case insensitive, the command has to start a line):
 *
 *     /tested ok|success|pass|passed [notes]
 *     /tested fail|failed|failure|unsuccessful [notes]
 *     /tested reset [@user]
 *
 * The notes are the rest of the command line plus all following lines of the comment.
 * Lines in fenced code blocks and quoted lines (`>`) are ignored, so quoting the command of someone else does not count.
 *
 * An optional environment block, e.g. added by the Patch Tester, reports the test environment:
 *
 *     <!-- jtracker-test-env {"joomla": "5.3.0", "php": "8.3.1", "db": "mysql 8.0", "sha": "<commit sha>"} -->
 *
 * @since  1.0
 */
class TestCommandParser
{
    /**
     * Regular expression for the environment block.
     *
     * @var    string
     * @since  1.0
     */
    public const ENVIRONMENT_PATTERN = '/<!--\s*jtracker-test-env\s+(\{.*?\})\s*-->/s';

    /**
     * Keywords for a successful test.
     *
     * @var    string[]
     * @since  1.0
     */
    private const SUCCESS_KEYWORDS = ['ok', 'success', 'successful', 'successfully', 'pass', 'passed'];

    /**
     * Keywords for a failed test.
     *
     * @var    string[]
     * @since  1.0
     */
    private const FAILURE_KEYWORDS = ['fail', 'failed', 'failure', 'unsuccessful', 'unsuccessfully'];

    /**
     * Parse a comment body.
     *
     * @param   string|null  $body  The comment body (markdown).
     *
     * @return  TestCommand|null  The first command found in the comment, null if the comment contains no command.
     *
     * @since   1.0
     */
    public function parse(?string $body): ?TestCommand
    {
        if ($body === null || stripos($body, '/tested') === false) {
            return null;
        }

        $body        = str_replace(["\r\n", "\r"], "\n", $body);
        $environment = $this->parseEnvironment($body);
        $body        = (string) preg_replace(self::ENVIRONMENT_PATTERN, '', $body);
        $lines       = explode("\n", $body);
        $inFence     = false;

        foreach ($lines as $index => $line) {
            // Toggle fenced code blocks
            if (preg_match('/^\s{0,3}(```|~~~)/', $line)) {
                $inFence = !$inFence;

                continue;
            }

            if ($inFence || preg_match('/^\s*>/', $line)) {
                continue;
            }

            if (!preg_match('#^\s{0,3}/tested(?=\s|$)\s*(\S*)(.*)$#i', $line, $matches)) {
                continue;
            }

            $keyword = strtolower(rtrim($matches[1], '.,:;!'));
            $rest    = trim($matches[2]);

            if ($keyword === 'reset') {
                $target = null;

                if (preg_match('/^@?([a-z\d][a-z\d-]{0,38})\b/i', $rest, $userMatch)) {
                    $target = $userMatch[1];
                }

                return new TestCommand(TestCommand::RESET, '', $target);
            }

            $notes = $this->collectNotes($rest, \array_slice($lines, $index + 1));

            if (\in_array($keyword, self::SUCCESS_KEYWORDS, true)) {
                return new TestCommand(TestCommand::SUCCESS, $notes, null, $environment);
            }

            if (\in_array($keyword, self::FAILURE_KEYWORDS, true)) {
                return new TestCommand(TestCommand::FAILURE, $notes, null, $environment);
            }

            return new TestCommand(TestCommand::INVALID);
        }

        return null;
    }

    /**
     * Collect the notes of a test: the rest of the command line and all following lines.
     *
     * @param   string    $rest       The rest of the command line.
     * @param   string[]  $nextLines  The lines following the command.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function collectNotes(string $rest, array $nextLines): string
    {
        // Allow a separator between the keyword and the notes, e.g. "/tested fail - the button is missing"
        $rest = (string) preg_replace('/^(?:-|:|\x{2013}|\x{2014})\s*/u', '', $rest);

        $notes = $rest . "\n" . implode("\n", $nextLines);

        // A horizontal rule starts a footer, e.g. the one of comments created with the tracker
        $notes = (string) preg_replace('/(?:^|\n)\s*<hr\s*\/?>.*$/is', '', $notes);

        return trim($notes);
    }

    /**
     * Parse the environment block.
     *
     * @param   string  $body  The comment body.
     *
     * @return  array
     *
     * @since   1.0
     */
    private function parseEnvironment(string $body): array
    {
        if (!preg_match(self::ENVIRONMENT_PATTERN, $body, $matches)) {
            return [];
        }

        $data = json_decode($matches[1], true);

        if (!\is_array($data)) {
            return [];
        }

        $environment = [];

        // Only keep scalar values with sane keys and a sane length
        foreach ($data as $key => $value) {
            if (\is_string($key) && preg_match('/^[a-z0-9_]{1,30}$/i', $key) && \is_scalar($value)) {
                $environment[strtolower($key)] = mb_substr((string) $value, 0, 100);
            }
        }

        return $environment;
    }
}
