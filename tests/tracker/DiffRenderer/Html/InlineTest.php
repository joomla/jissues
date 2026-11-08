<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\DiffRenderer\Html;

use Adaptive\Diff\Diff;
use JTracker\DiffRenderer\Html\Inline;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Tests for the Inline diff renderer.
 *
 * The diff opcodes are provided by a mocked Diff object so the tests do not depend on the
 * sequence matcher of the diff library. The output is rendered with the real diff.twig template.
 *
 * @since  1.0
 */
class InlineTest extends TestCase
{
    /**
     * Renders a diff.
     *
     * @param   string[]  $old      The old lines
     * @param   string[]  $new      The new lines
     * @param   array     $opCodes  The grouped opcodes
     * @param   array     $options  The renderer options
     *
     * @return  string  The rendered HTML with the whitespace between tags removed
     */
    private function render(array $old, array $new, array $opCodes, array $options = []): string
    {
        $diff = $this->createMock(Diff::class);
        $diff->method('getA')->willReturn($old);
        $diff->method('getB')->willReturn($new);
        $diff->method('getGroupedOpcodes')->willReturn($opCodes);

        $renderer       = new Inline(new Environment(new FilesystemLoader(JPATH_TEMPLATES)), $options);
        $renderer->diff = $diff;

        return preg_replace('/>\s+</', '><', trim($renderer->render()));
    }

    /**
     * Returns the rendered <tbody> elements for a change type.
     *
     * @param   string  $html  The rendered HTML
     * @param   string  $type  The change type, e.g. "Insert"
     *
     * @return  string[]
     */
    private function bodies(string $html, string $type): array
    {
        preg_match_all('#<tbody class="Change' . $type . '">(.*?)</tbody>#', $html, $matches);

        return $matches[1];
    }

    /**
     * @testdox  An empty diff renders nothing
     *
     * @return  void
     */
    public function testRenderEmptyDiff(): void
    {
        $this->assertSame('', $this->render(['a', 'b'], ['a', 'b'], []));
    }

    /**
     * @testdox  The header and line numbers are rendered by default
     *
     * @return  void
     */
    public function testRenderHeaderAndLineNumbersByDefault(): void
    {
        $html = $this->render(['a'], ['a', 'b'], [[['equal', 0, 1, 0, 1], ['insert', 1, 1, 1, 2]]]);

        $this->assertStringStartsWith('<table class="Differences DifferencesInline">', $html);
        $this->assertStringContainsString('<thead><tr><th>Old</th><th>New</th><th>Differences</th></tr></thead>', $html);
        $this->assertStringEndsWith('</table>', $html);
    }

    /**
     * @testdox  The header and line numbers can be disabled
     *
     * @return  void
     */
    public function testRenderWithoutHeaderAndLineNumbers(): void
    {
        $html = $this->render(
            ['a'],
            ['a', 'b'],
            [[['equal', 0, 1, 0, 1], ['insert', 1, 1, 1, 2]]],
            ['show_header' => false, 'show_line_numbers' => false]
        );

        $this->assertStringNotContainsString('<thead>', $html);
        $this->assertSame(['<tr><td class="Left">a</td></tr>'], $this->bodies($html, 'Equal'));
        $this->assertSame(['<tr><td class="Right"><ins>b</ins></td></tr>'], $this->bodies($html, 'Insert'));
    }

    /**
     * @testdox  The renderer passes the changes and display options to the diff template
     *
     * @return  void
     */
    public function testRenderPassesOptionsToTemplate(): void
    {
        $diff = $this->createMock(Diff::class);
        $diff->method('getA')->willReturn(['a']);
        $diff->method('getB')->willReturn(['b']);
        $diff->method('getGroupedOpcodes')->willReturn([[['replace', 0, 1, 0, 1]]]);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                'diff.twig',
                $this->callback(function (array $context): bool {
                    $this->assertFalse($context['show_header']);
                    $this->assertTrue($context['show_line_numbers']);
                    $this->assertSame('replace', $context['changes'][0][0]['tag']);

                    return true;
                })
            )
            ->willReturn('rendered');

        $renderer       = new Inline($twig, ['show_header' => false]);
        $renderer->diff = $diff;

        $this->assertSame('rendered', $renderer->render());
    }

    /**
     * @testdox  Unchanged lines are shown with both line numbers
     *
     * @return  void
     */
    public function testRenderUnchangedLines(): void
    {
        $html = $this->render(
            ['a', 'b', 'c'],
            ['x', 'b', 'c'],
            [[['replace', 0, 1, 0, 1], ['equal', 1, 3, 1, 3]]]
        );

        $this->assertSame(
            ['<tr><td>2</td><td>2</td><td class="Left">b</td></tr><tr><td>3</td><td>3</td><td class="Left">c</td></tr>'],
            $this->bodies($html, 'Equal')
        );
    }

    /**
     * @testdox  Inserted lines are shown with the new line number only
     *
     * @return  void
     */
    public function testRenderInsertedLines(): void
    {
        $html = $this->render(['a'], ['a', 'b', 'c'], [[['equal', 0, 1, 0, 1], ['insert', 1, 1, 1, 3]]]);

        $this->assertSame(
            ['<tr><td>&nbsp;</td><td>2</td><td class="Right"><ins>b</ins></td></tr>'
                . '<tr><td>&nbsp;</td><td>3</td><td class="Right"><ins>c</ins></td></tr>'],
            $this->bodies($html, 'Insert')
        );
    }

    /**
     * @testdox  Deleted lines are shown with the old line number only
     *
     * @return  void
     */
    public function testRenderDeletedLines(): void
    {
        $html = $this->render(['a', 'b'], ['a'], [[['equal', 0, 1, 0, 1], ['delete', 1, 2, 1, 1]]]);

        $this->assertSame(
            ['<tr><td>2</td><td>&nbsp;</td><td class="Left"><del>b</del></td></tr>'],
            $this->bodies($html, 'Delete')
        );
    }

    /**
     * @testdox  Changed lines are shown on both sides with the changed characters marked
     *
     * @return  void
     */
    public function testRenderChangedLines(): void
    {
        $html = $this->render(['foo bar baz'], ['foo qux baz'], [[['replace', 0, 1, 0, 1]]]);

        $this->assertSame(
            ['<tr><td>1</td><td>&nbsp;</td><td class="Left"><span>foo <del>bar</del> baz</span></td></tr>'
                . '<tr><td>&nbsp;</td><td>1</td><td class="Right"><span>foo <ins>qux</ins> baz</span></td></tr>'],
            $this->bodies($html, 'Replace')
        );
    }

    /**
     * @testdox  Changed blocks of different length are shown without character markers
     *
     * @return  void
     */
    public function testRenderChangedBlocksOfDifferentLength(): void
    {
        $html = $this->render(['a'], ['b', 'c'], [[['replace', 0, 1, 0, 2]]]);

        $this->assertSame(
            ['<tr><td>1</td><td>&nbsp;</td><td class="Left"><span>a</span></td></tr>'
                . '<tr><td>&nbsp;</td><td>1</td><td class="Right"><span>b</span></td></tr>'
                . '<tr><td>&nbsp;</td><td>2</td><td class="Right"><span>c</span></td></tr>'],
            $this->bodies($html, 'Replace')
        );
    }

    /**
     * @testdox  HTML special characters in the compared lines are escaped
     *
     * @return  void
     */
    public function testRenderEscapesHtml(): void
    {
        $html = $this->render(
            ['<b>x</b>', 'same & <script>alert(1)</script>', 'gone <img src=x onerror=alert(1)>'],
            ['<i>x</i>', 'same & <script>alert(1)</script>', 'new <a href="#">'],
            [[['replace', 0, 1, 0, 1], ['equal', 1, 2, 1, 2], ['delete', 2, 3, 2, 2], ['insert', 3, 3, 2, 3]]]
        );

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<a href', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringNotContainsString('<i>', $html);

        $this->assertStringContainsString('<span>&lt;<del>b&gt;x&lt;/b</del>&gt;</span>', $html);
        $this->assertStringContainsString('<td class="Left">same &amp; &lt;script&gt;alert(1)&lt;/script&gt;</td>', $html);
        $this->assertStringContainsString('<del>gone &lt;img src=x onerror=alert(1)&gt;</del>', $html);
        $this->assertStringContainsString('<ins>new &lt;a href="#"&gt;</ins>', $html);
    }

    /**
     * @testdox  Tabs are expanded and runs of spaces are kept as non-breaking spaces
     *
     * @return  void
     */
    public function testRenderTabsAndSpaces(): void
    {
        $html = $this->render(["\tfoo", 'a    b'], ["\tfoo", 'a    b'], [[['equal', 0, 2, 0, 2]]]);

        $this->assertStringNotContainsString("\t", $html);
        $this->assertStringContainsString('<td class="Left">&nbsp; &nbsp;foo</td>', $html);
        $this->assertStringContainsString('<td class="Left">a&nbsp; &nbsp;b</td>', $html);
    }

    /**
     * @testdox  The tab size option controls the width of expanded tabs
     *
     * @return  void
     */
    public function testRenderTabSize(): void
    {
        $html = $this->render(["\tfoo"], ["\tfoo"], [[['equal', 0, 1, 0, 1]]], ['tabSize' => 8]);

        $this->assertStringContainsString('<td class="Left">&nbsp; &nbsp; &nbsp; &nbsp;foo</td>', $html);
    }

    /**
     * @testdox  Separate groups of changes are divided by a skipped lines marker and keep their line numbers
     *
     * @return  void
     */
    public function testRenderMultipleGroups(): void
    {
        $old = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];
        $new = ['A', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'J'];

        $html = $this->render($old, $new, [
            [['replace', 0, 1, 0, 1], ['equal', 1, 2, 1, 2]],
            [['equal', 8, 9, 8, 9], ['replace', 9, 10, 9, 10]],
        ]);

        $this->assertSame(2, substr_count($html, '<tbody class="ChangeReplace">'));
        $this->assertSame(2, substr_count($html, '<tbody class="ChangeEqual">'));
        $this->assertStringContainsString(
            '<td class="Left">b</td></tr></tbody><tbody class="Skipped"><tr><td>&hellip;</td><td>&nbsp;</td><td>&hellip;</td></tr></tbody>'
                . '<tbody class="ChangeEqual"><tr><td>9</td>',
            $html
        );

        // The second group keeps the line numbers of the original text
        $this->assertStringContainsString('<tr><td>9</td><td>9</td><td class="Left">i</td></tr>', $html);
        $this->assertStringContainsString('<tr><td>10</td><td>&nbsp;</td><td class="Left"><span>j</span></td></tr>', $html);
        $this->assertStringContainsString('<tr><td>&nbsp;</td><td>10</td><td class="Right"><span>J</span></td></tr>', $html);
    }
}
