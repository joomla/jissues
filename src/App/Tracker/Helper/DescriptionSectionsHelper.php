<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Helper;

/**
 * Replaces sections of a pull request description, such as the testing instructions of the joomla-cms PR template.
 *
 * A section starts with a Markdown heading (`#` to `######`) and ends before the next heading of the same or a higher
 * level. Sections are matched by their heading text, so "### Testing Instructions" in the proposal replaces the
 * section "### Testing instructions" in the description. The description keeps its own heading line.
 *
 * @since  1.0
 */
class DescriptionSectionsHelper
{
    /**
     * Heading of the section used for a proposal without headings
     */
    public const TESTING_HEADING = '### Testing Instructions';

    /**
     * Headings before which new sections are inserted, if the description has them
     */
    private const INSERT_BEFORE = ['link to documentations', 'link to documentation'];

    /**
     * Replace the sections of a description with the sections of a proposal.
     *
     * Each section of the proposal replaces the section with the same heading in the description. A section the
     * description doesn't have yet is inserted before "Link to documentations" or, without that heading, appended.
     * A proposal without any heading is treated as the content of "### Testing Instructions". The line ending style
     * of the description is kept.
     *
     * @param   string  $description  The current description.
     * @param   string  $proposal     The proposal, Markdown with headings.
     *
     * @return  string
     *
     * @since   1.0
     */
    public static function replaceSections(string $description, string $proposal): string
    {
        $crlf        = str_contains($description, "\r\n");
        $description = str_replace("\r\n", "\n", $description);
        $proposal    = trim(str_replace("\r\n", "\n", $proposal));

        $sections = self::parseSections($proposal);

        if ($sections === []) {
            $sections = [['heading' => self::TESTING_HEADING, 'content' => $proposal]];
        }

        $lines = explode("\n", $description);

        foreach ($sections as $section) {
            $lines = self::replaceSection($lines, $section['heading'], $section['content']);
        }

        $result = implode("\n", $lines);

        return $crlf ? str_replace("\n", "\r\n", $result) : $result;
    }

    /**
     * Get the content of a section, without its heading.
     *
     * Only the text up to the next heading is returned, so subsections are not included.
     *
     * @param   string  $description  The description.
     * @param   string  $heading      The heading of the section, for example "### Testing Instructions".
     *
     * @return  string|null  The content, or null if the description has no such section.
     *
     * @since   1.0
     */
    public static function getSection(string $description, string $heading): ?string
    {
        $key = self::headingKey($heading);

        foreach (self::parseSections(str_replace("\r\n", "\n", $description)) as $section) {
            if (self::headingKey($section['heading']) === $key) {
                return $section['content'];
            }
        }

        return null;
    }

    /**
     * Normalised key of a heading line, used to match sections: lower case text without the hashes and markup.
     *
     * @param   string  $heading  The heading line.
     *
     * @return  string
     *
     * @since   1.0
     */
    public static function headingKey(string $heading): string
    {
        $text = strtolower(trim(preg_replace('/^#{1,6}\s*|\s*#*\s*$/', '', trim($heading))));
        $text = preg_replace('/[*_`:]+/', '', $text);

        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     * Split Markdown into its sections. Text before the first heading is ignored.
     *
     * @param   string  $markdown  The Markdown with LF line endings.
     *
     * @return  array<int, array{heading: string, content: string}>
     *
     * @since   1.0
     */
    private static function parseSections(string $markdown): array
    {
        $sections = [];
        $current  = null;
        $fence    = false;

        foreach (explode("\n", $markdown) as $line) {
            if (preg_match('/^\s*(```|~~~)/', $line)) {
                $fence = !$fence;
            }

            if (!$fence && self::headingLevel($line) > 0) {
                if ($current !== null) {
                    $sections[] = $current;
                }

                $current = ['heading' => rtrim($line), 'content' => ''];

                continue;
            }

            if ($current !== null) {
                $current['content'] .= $line . "\n";
            }
        }

        if ($current !== null) {
            $sections[] = $current;
        }

        foreach ($sections as &$section) {
            $section['content'] = trim($section['content'], "\n");
        }

        return $sections;
    }

    /**
     * Replace or insert one section.
     *
     * @param   string[]  $lines    The description lines.
     * @param   string    $heading  The heading line of the section.
     * @param   string    $content  The new content, without the heading.
     *
     * @return  string[]
     *
     * @since   1.0
     */
    private static function replaceSection(array $lines, string $heading, string $content): array
    {
        $key        = self::headingKey($heading);
        $headings   = self::findHeadings($lines);
        $newContent = array_merge([''], $content === '' ? [] : explode("\n", $content), ['']);

        foreach ($headings as $index => $found) {
            if (self::headingKey($lines[$found['line']]) !== $key) {
                continue;
            }

            // The section ends before the next heading of the same or a higher level
            $end = \count($lines);

            foreach (\array_slice($headings, $index + 1) as $next) {
                if ($next['level'] <= $found['level']) {
                    $end = $next['line'];

                    break;
                }
            }

            // If the section was the last one, keep the description's own trailing blank lines
            if ($end === \count($lines)) {
                $trailing = 0;

                for ($i = $end - 1; $i > $found['line'] && trim($lines[$i]) === ''; $i--) {
                    $trailing++;
                }

                $newContent = array_merge(\array_slice($newContent, 0, -1), array_fill(0, $trailing, ''));
            }

            array_splice($lines, $found['line'] + 1, $end - $found['line'] - 1, $newContent);

            return $lines;
        }

        // Not found: insert before "Link to documentations", else append
        $insert = array_merge([$heading], $newContent);

        foreach ($headings as $found) {
            if (\in_array(self::headingKey($lines[$found['line']]), self::INSERT_BEFORE, true)) {
                array_splice($lines, $found['line'], 0, $insert);

                return $lines;
            }
        }

        while ($lines !== [] && trim(end($lines)) === '') {
            array_pop($lines);
        }

        if ($lines !== []) {
            $lines[] = '';
        }

        return array_merge($lines, \array_slice($insert, 0, -1));
    }

    /**
     * Find the heading lines outside of code fences.
     *
     * @param   string[]  $lines  The lines.
     *
     * @return  array<int, array{line: int, level: int}>
     *
     * @since   1.0
     */
    private static function findHeadings(array $lines): array
    {
        $headings = [];
        $fence    = false;

        foreach ($lines as $number => $line) {
            if (preg_match('/^\s*(```|~~~)/', $line)) {
                $fence = !$fence;

                continue;
            }

            $level = $fence ? 0 : self::headingLevel($line);

            if ($level > 0) {
                $headings[] = ['line' => $number, 'level' => $level];
            }
        }

        return $headings;
    }

    /**
     * Level of an ATX heading line, 0 if the line is no heading.
     *
     * @param   string  $line  The line.
     *
     * @return  integer
     *
     * @since   1.0
     */
    private static function headingLevel(string $line): int
    {
        return preg_match('/^ {0,3}(#{1,6})(\s|$)/', $line, $matches) ? \strlen($matches[1]) : 0;
    }
}
