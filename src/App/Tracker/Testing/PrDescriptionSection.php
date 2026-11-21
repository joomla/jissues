<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * Renders, replaces and strips the bot managed test section at the bottom of a pull request description.
 *
 * The section is enclosed in HTML comment markers. Everything outside the markers belongs to the author of the pull request
 * and is never changed.
 *
 * @since  1.0
 */
class PrDescriptionSection
{
    /**
     * Start marker of the section.
     *
     * @var    string
     * @since  1.0
     */
    public const START = '<!-- jtracker:test-status:start -->';

    /**
     * End marker of the section.
     *
     * @var    string
     * @since  1.0
     */
    public const END = '<!-- jtracker:test-status:end -->';

    /**
     * Maximum length of the notes shown in the results table.
     *
     * @var    integer
     * @since  1.0
     */
    private const NOTES_LENGTH = 100;

    /**
     * Constructor.
     *
     * @param   string|null  $instructionsFile  The markdown file with the testing instructions.
     *
     * @since   1.0
     */
    public function __construct(private readonly ?string $instructionsFile = null)
    {
    }

    /**
     * Remove the section from a pull request description.
     *
     * @param   string|null  $body  The pull request description.
     *
     * @return  string  The text of the author.
     *
     * @since   1.0
     */
    public static function strip(?string $body): string
    {
        $body = (string) $body;

        $start = strpos($body, self::START);

        if ($start === false) {
            return $body;
        }

        $end   = strpos($body, self::END, $start);
        $after = $end === false ? '' : substr($body, $end + \strlen(self::END));

        return rtrim(substr($body, 0, $start)) . (trim($after) === '' ? '' : "\r\n\r\n" . ltrim($after));
    }

    /**
     * Extract the section from a pull request description.
     *
     * @param   string|null  $body  The pull request description.
     *
     * @return  string|null  The section including the markers, null if the description has no section.
     *
     * @since   1.0
     */
    public static function extract(?string $body): ?string
    {
        $body  = (string) $body;
        $start = strpos($body, self::START);

        if ($start === false) {
            return null;
        }

        $end = strpos($body, self::END, $start);

        if ($end === false) {
            return null;
        }

        return substr($body, $start, $end - $start + \strlen(self::END));
    }

    /**
     * Put a section into a pull request description, replacing an existing section.
     *
     * @param   string|null  $body     The pull request description.
     * @param   string       $section  The rendered section.
     *
     * @return  string
     *
     * @since   1.0
     */
    public static function replace(?string $body, string $section): string
    {
        $text = rtrim(self::strip($body));

        return ($text === '' ? '' : $text . "\r\n\r\n") . $section;
    }

    /**
     * Render the section.
     *
     * @param   TestSummary  $summary  The test results.
     * @param   array        $context  Values for the section: repository (owner/repo), number, author, bot, trackerUrl, environmentUrl.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function render(TestSummary $summary, array $context): string
    {
        $repository     = (string) ($context['repository'] ?? '');
        $number         = (int) ($context['number'] ?? 0);
        $bot            = (string) ($context['bot'] ?? 'joomla-bot');
        $trackerUrl     = (string) ($context['trackerUrl'] ?? '');
        $environmentUrl = $context['environmentUrl'] ?? null;

        $successes = \count($summary->getSuccessful());
        $failures  = \count($summary->getFailed());

        $lines   = [];
        $lines[] = self::START;
        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';
        $lines[] = '### Human test results';
        $lines[] = '';
        $lines[] = \sprintf(
            '%1$s **%2$d successful**, %3$s **%4$d failed** test%5$s for the current changes. %6$s',
            "\u{2705}",
            $successes,
            "\u{274C}",
            $failures,
            $failures === 1 ? '' : 's',
            $this->getStateText($summary)
        );
        $lines[] = '';

        if ($summary->current || $summary->outdated) {
            $lines[] = '| Tester | Result | Tested commit | Date | Notes |';
            $lines[] = '|---|---|---|---|---|';

            foreach ($summary->current as $result) {
                $lines[] = $this->renderRow($result, $repository, $number, false);
            }

            foreach ($summary->outdated as $result) {
                $lines[] = $this->renderRow($result, $repository, $number, true);
            }

            $lines[] = '';
        }

        if (\is_string($environmentUrl) && $environmentUrl !== '') {
            $lines[] = \sprintf('%1$s **Test environment:** [Open a site with this pull request applied](%2$s)', "\u{1F9EA}", $environmentUrl);
            $lines[] = '';
        }

        $lines[] = '<details>';
        $lines[] = '<summary>How to test this pull request</summary>';
        $lines[] = '';
        $lines[] = $this->getInstructions($context);
        $lines[] = '';
        $lines[] = '</details>';
        $lines[] = '';
        $lines[] = \sprintf(
            '<sub>This section is updated automatically by %1$s%2$s. Changes to it will be overwritten.</sub>',
            $this->userLink($bot),
            $trackerUrl !== '' ? ' from the [J!Tracker](' . $trackerUrl . ')' : ''
        );
        $lines[] = self::END;

        return implode("\r\n", $lines);
    }

    /**
     * Render a row of the results table.
     *
     * @param   TestResult  $result      The test result.
     * @param   string      $repository  The repository (owner/repo).
     * @param   integer     $number      The pull request number.
     * @param   boolean     $outdated    Whether the result is outdated.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function renderRow(TestResult $result, string $repository, int $number, bool $outdated): string
    {
        $text = $result->result === TestResult::SUCCESS ? "\u{2705} Success" : "\u{274C} Failure";

        if ($outdated) {
            $text = '~~' . $text . '~~ outdated';
        }

        $sha       = (string) $result->getTestedSha();
        $shaColumn = $sha !== '' ? '`' . substr($sha, 0, 7) . '`' : '';

        if ($sha !== '' && $repository !== '') {
            $shaColumn = '[' . $shaColumn . '](https://github.com/' . $repository . '/commit/' . $sha . ')';
        }

        $date = $result->createdDate ? substr($result->createdDate, 0, 10) : '';

        $notes = $this->escapeCell($result->comment);

        if ($result->ghCommentId && $repository !== '') {
            $notes = trim($notes . ' [comment](https://github.com/' . $repository . '/pull/' . $number . '#issuecomment-' . $result->ghCommentId . ')');
        }

        return \sprintf('| %1$s | %2$s | %3$s | %4$s | %5$s |', $this->userLink($result->username), $text, $shaColumn, $date, $notes);
    }

    /**
     * Get the text describing the state of the tests.
     *
     * @param   TestSummary  $summary  The test results.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getStateText(TestSummary $summary): string
    {
        return match ($summary->getState()) {
            'success' => 'The pull request has enough successful tests.',
            'failure' => 'The pull request failed the tests.',
            default   => \sprintf('%d successful tests by different people are needed.', TestSummary::REQUIRED_SUCCESSES),
        };
    }

    /**
     * Get the testing instructions.
     *
     * @param   array  $context  The values for the placeholders.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getInstructions(array $context): string
    {
        $file = $this->instructionsFile ?? (\defined('JPATH_TEMPLATES') ? JPATH_TEMPLATES : \dirname(__DIR__, 4) . '/templates')
            . '/tracker/github/test-instructions.md';

        $text = is_file($file) ? (string) file_get_contents($file) : 'Comment `/tested ok` or `/tested fail` with your notes after testing.';

        return trim(
            strtr(
                str_replace(["\r\n", "\r"], "\n", $text),
                [
                    '{required}'   => (string) TestSummary::REQUIRED_SUCCESSES,
                    '{bot}'        => (string) ($context['bot'] ?? 'joomla-bot'),
                    '{trackerUrl}' => (string) ($context['trackerUrl'] ?? ''),
                    '{author}'     => (string) ($context['author'] ?? ''),
                ]
            )
        );
    }

    /**
     * Create a link to a GitHub user without mentioning (and notifying) the user.
     *
     * @param   string  $login  The GitHub login.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function userLink(string $login): string
    {
        return '[' . $login . '](https://github.com/' . rawurlencode($login) . ')';
    }

    /**
     * Escape a text for a markdown table cell.
     *
     * @param   string  $text  The text.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function escapeCell(string $text): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        if (mb_strlen($text) > self::NOTES_LENGTH) {
            $text = rtrim(mb_substr($text, 0, self::NOTES_LENGTH - 1)) . "\u{2026}";
        }

        // Neutralise markup which could break the table or the section, and mentions which would notify users
        return strtr(
            htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8'),
            [
                '|' => '\|',
                '@' => '@&#8203;',
                '`' => '\`',
            ]
        );
    }
}
