<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Testing;

use App\Tracker\Testing\PrDescriptionSection;
use App\Tracker\Testing\TestResult;
use App\Tracker\Testing\TestSummary;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the test section in the pull request description.
 *
 * @since  1.0
 */
class PrDescriptionSectionTest extends TestCase
{
    /**
     * Render a section.
     *
     * @param   TestResult[]  $results         The test results.
     * @param   string|null   $environmentUrl  The URL of the test environment.
     *
     * @return  string
     */
    private function render(array $results = [], ?string $environmentUrl = null): string
    {
        return (new PrDescriptionSection(JPATH_ROOT . '/templates/tracker/github/test-instructions.md'))->render(
            new TestSummary($results, 'head'),
            [
                'repository'     => 'joomla-extensions/weblinks',
                'number'         => 42,
                'author'         => 'author',
                'bot'            => 'joomla-bot',
                'trackerUrl'     => 'https://issues.joomla.org/tracker/weblinks/42',
                'environmentUrl' => $environmentUrl,
            ]
        );
    }

    /**
     * The section is appended to a description without a section.
     *
     * @return  void
     */
    public function testAppend(): void
    {
        $section = $this->render();
        $body    = PrDescriptionSection::replace("Summary of changes\r\n\r\nTest instructions\r\n", $section);

        $this->assertStringStartsWith("Summary of changes\r\n\r\nTest instructions\r\n\r\n" . PrDescriptionSection::START, $body);
        $this->assertStringEndsWith(PrDescriptionSection::END, $body);
        $this->assertSame("Summary of changes\r\n\r\nTest instructions", PrDescriptionSection::strip($body));
    }

    /**
     * An empty description only gets the section.
     *
     * @return  void
     */
    public function testEmptyBody(): void
    {
        $section = $this->render();

        $this->assertSame($section, PrDescriptionSection::replace(null, $section));
        $this->assertSame('', PrDescriptionSection::strip($section));
    }

    /**
     * Replacing a section keeps the text of the author before and after the section.
     *
     * @return  void
     */
    public function testReplaceKeepsAuthorText(): void
    {
        $old  = $this->render();
        $body = "Text of the author\r\n\r\n" . $old . "\r\n\r\nText added below by the author";
        $new  = $this->render([new TestResult(1, 1, 'tester', TestResult::SUCCESS, 'head', 'head', 'hash', '2026-11-21 10:00:00', 99)]);

        $result = PrDescriptionSection::replace($body, $new);

        $this->assertSame("Text of the author\r\n\r\nText added below by the author\r\n\r\n" . $new, $result);
        $this->assertSame($new, PrDescriptionSection::extract($result));
    }

    /**
     * Replacing a section with the same section does not change the description.
     *
     * @return  void
     */
    public function testReplaceIsIdempotent(): void
    {
        $section = $this->render();
        $body    = PrDescriptionSection::replace('Text', $section);

        $this->assertSame($body, PrDescriptionSection::replace($body, $section));
    }

    /**
     * A section without an end marker (e.g. cut off by the author) is removed up to the end.
     *
     * @return  void
     */
    public function testMissingEndMarker(): void
    {
        $body = "Text\r\n\r\n" . PrDescriptionSection::START . "\r\nrest of the old section";

        $this->assertSame('Text', PrDescriptionSection::strip($body));
        $this->assertNull(PrDescriptionSection::extract($body));
    }

    /**
     * The section shows the results, the instructions and only optionally the test environment.
     *
     * @return  void
     */
    public function testContent(): void
    {
        $results = [
            new TestResult(1, 1, 'alice', TestResult::SUCCESS, 'head', 'head', 'hash', '2026-11-20 10:00:00', 11, 'Works | fine @someone'),
            new TestResult(2, 1, 'bob', TestResult::FAILURE, 'old', 'old', 'oldhash', '2026-11-19 10:00:00', 12, 'Broken'),
        ];

        $section = $this->render($results);

        $this->assertStringContainsString('**1 successful**', $section);
        $this->assertStringContainsString('**0 failed** tests', $section);
        $this->assertStringContainsString('| [alice](https://github.com/alice) |', $section);
        $this->assertStringContainsString('Works \| fine @&#8203;someone', $section);
        $this->assertStringContainsString('#issuecomment-11', $section);
        $this->assertStringContainsString('~~' . "\u{274C}" . ' Failure~~ outdated', $section);
        $this->assertStringContainsString('<summary>How to test this pull request</summary>', $section);
        $this->assertStringContainsString('The author of the pull request (author)', $section);
        $this->assertStringNotContainsString('Test environment', $section);
        $this->assertStringNotContainsString('{required}', $section);

        $this->assertStringContainsString(
            '**Test environment:** [Open a site with this pull request applied](https://example.org/pr-42)',
            $this->render([], 'https://example.org/pr-42')
        );
    }
}
