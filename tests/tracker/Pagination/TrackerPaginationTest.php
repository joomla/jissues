<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Pagination;

use Joomla\Uri\Uri;
use JTracker\Pagination\TrackerPagination;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the TrackerPagination class.
 *
 * @since  1.0
 */
class TrackerPaginationTest extends TestCase
{
    /**
     * Base URI used for the links
     *
     * @var  string
     */
    private const BASE_URI = 'https://issues.example.org/text';

    /**
     * Creates a pagination object.
     *
     * @param   integer  $total    Total items
     * @param   integer  $current  Current item (offset)
     * @param   integer  $perPage  Items per page
     *
     * @return  TrackerPagination
     */
    private function createPagination(int $total, int $current, int $perPage): TrackerPagination
    {
        return (new TrackerPagination(new Uri(self::BASE_URI)))->setValues($total, $current, $perPage);
    }

    /**
     * Creates a pagination object showing the given page.
     *
     * @param   integer  $page   The current page
     * @param   integer  $pages  Total pages
     *
     * @return  TrackerPagination
     */
    private function createPaginationForPage(int $page, int $pages): TrackerPagination
    {
        return $this->createPagination($pages * 10, ($page - 1) * 10, 10);
    }

    /**
     * Returns the labels of all list items in a bar.
     *
     * @param   string  $bar  The rendered bar
     *
     * @return  string[]
     */
    private function labels(string $bar): array
    {
        preg_match_all('#<li[^>]*>(?:<a[^>]*>)?([^<]+)(?:</a>)?</li>#', $bar, $matches);

        return $matches[1];
    }

    /**
     * Returns the expected link list item for a page.
     *
     * @param   integer  $page   The page number
     * @param   string   $label  The link label, defaults to the page number
     *
     * @return  string
     */
    private function link(int $page, string $label = ''): string
    {
        return '<li class="page-item"><a class="page-link" href="' . self::BASE_URI . '?page=' . $page . '">'
            . ($label ?: $page) . '</a></li>';
    }

    /**
     * Returns the expected list item for the active page.
     *
     * @param   integer  $page  The page number
     *
     * @return  string
     */
    private function active(int $page): string
    {
        return '<li class="page-item active"><a class="page-link" href="#" aria-current="page">' . $page . '</a></li>';
    }

    /**
     * Data provider for testSetValuesCalculatesPageNumber
     *
     * @return  \Generator
     */
    public function pageNumberProvider(): \Generator
    {
        yield 'no offset' => [0, 20, 1];
        yield 'within first page' => [19, 20, 1];
        yield 'start of second page' => [20, 20, 2];
        yield 'within third page' => [45, 20, 3];
    }

    /**
     * @testdox  setValues() calculates the current page from the item offset
     *
     * @param   integer  $current  Current item (offset)
     * @param   integer  $perPage  Items per page
     * @param   integer  $page     Expected page number
     *
     * @return  void
     *
     * @dataProvider  pageNumberProvider
     */
    public function testSetValuesCalculatesPageNumber(int $current, int $perPage, int $page): void
    {
        $this->assertEquals($page, $this->createPagination(100, $current, $perPage)->getPageNo());
    }

    /**
     * @testdox  setValues() returns the object for chaining
     *
     * @return  void
     */
    public function testSetValuesIsChainable(): void
    {
        $pagination = new TrackerPagination(new Uri(self::BASE_URI));

        $this->assertSame($pagination, $pagination->setValues(10, 0, 5));
    }

    /**
     * @testdox  getPageNo() returns 1 when no values have been set
     *
     * @return  void
     */
    public function testGetPageNoDefault(): void
    {
        $this->assertEquals(1, (new TrackerPagination(new Uri(self::BASE_URI)))->getPageNo());
    }

    /**
     * Data provider for testGetPagesTotal
     *
     * @return  \Generator
     */
    public function pagesTotalProvider(): \Generator
    {
        yield 'no items' => [0, 20, 0];
        yield 'single item' => [1, 20, 1];
        yield 'exactly one page' => [20, 20, 1];
        yield 'partial last page' => [21, 20, 2];
        yield 'zero items per page' => [3, 0, 3];
    }

    /**
     * @testdox  getPagesTotal() rounds up the number of pages
     *
     * @param   integer  $total    Total items
     * @param   integer  $perPage  Items per page
     * @param   integer  $pages    Expected number of pages
     *
     * @return  void
     *
     * @dataProvider  pagesTotalProvider
     */
    public function testGetPagesTotal(int $total, int $perPage, int $pages): void
    {
        $this->assertEquals($pages, $this->createPagination($total, 0, $perPage)->getPagesTotal());
    }

    /**
     * @testdox  getBar() renders nothing when there is only one page
     *
     * @return  void
     */
    public function testGetBarSinglePage(): void
    {
        $this->assertEmpty($this->createPagination(20, 0, 20)->getBar());
        $this->assertEmpty($this->createPagination(0, 0, 20)->getBar());
    }

    /**
     * @testdox  getBar() renders all pages when there are only a few
     *
     * @return  void
     */
    public function testGetBarFewPages(): void
    {
        $bar = $this->createPaginationForPage(2, 3)->getBar();

        $expected = implode("\n", [
            '<ul class="trackerPagination pagination">',
            $this->link(1),
            $this->active(2),
            $this->link(3),
            $this->link(3, 'Next'),
            $this->link(3, 'Last'),
            '</ul>',
        ]);

        $this->assertSame($expected, $bar);
    }

    /**
     * @testdox  getBar() disables the Next and Last links on the last page
     *
     * @return  void
     */
    public function testGetBarLastPage(): void
    {
        $bar = $this->createPaginationForPage(3, 3)->getBar();

        $this->assertSame(['1', '2', '3', 'Next', 'Last'], $this->labels($bar));
        $this->assertStringContainsString($this->active(3), $bar);
        $this->assertStringContainsString(
            '<li class="page-item disabled"><a tabindex="-1" class="page-link" href="#" aria-current="page">Next</a></li>',
            $bar
        );
        $this->assertStringContainsString(
            '<li class="page-item disabled"><a tabindex="-1" class="page-link" href="#" aria-current="page">Last</a></li>',
            $bar
        );
        $this->assertStringNotContainsString('page=4', $bar);
    }

    /**
     * @testdox  getBar() renders all ten pages without an ellipsis
     *
     * @return  void
     */
    public function testGetBarTenPages(): void
    {
        $bar = $this->createPaginationForPage(1, 10)->getBar();

        $this->assertSame(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'Next', 'Last'], $this->labels($bar));
        $this->assertStringContainsString($this->active(1), $bar);
        $this->assertStringContainsString($this->link(2, 'Next'), $bar);
        $this->assertStringContainsString($this->link(10, 'Last'), $bar);
    }

    /**
     * Data provider for testGetBarEllipsis
     *
     * @return  \Generator
     */
    public function ellipsisProvider(): \Generator
    {
        yield 'first page' => [1, 20, ['1', '2', '3', '4', '5', '6', '7', '...', '19', '20', 'Next', 'Last']];
        yield 'near the start' => [4, 20, ['1', '2', '3', '4', '5', '6', '7', '...', '19', '20', 'Next', 'Last']];
        yield 'middle' => [10, 20, ['1', '2', '...', '8', '9', '10', '11', '12', '..', '19', '20', 'Next', 'Last']];
        yield 'last middle page' => [15, 20, ['1', '2', '...', '13', '14', '15', '16', '17', '..', '19', '20', 'Next', 'Last']];
        yield 'near the end' => [16, 20, ['1', '2', '..', '14', '15', '16', '17', '18', '19', '20', 'Next', 'Last']];
        yield 'last page' => [20, 20, ['1', '2', '..', '14', '15', '16', '17', '18', '19', '20', 'Next', 'Last']];
    }

    /**
     * @testdox  getBar() collapses page ranges with an ellipsis when there are many pages
     *
     * @param   integer   $page    The current page
     * @param   integer   $pages   Total pages
     * @param   string[]  $labels  The expected labels
     *
     * @return  void
     *
     * @dataProvider  ellipsisProvider
     */
    public function testGetBarEllipsis(int $page, int $pages, array $labels): void
    {
        $bar = $this->createPaginationForPage($page, $pages)->getBar();

        $this->assertSame($labels, $this->labels($bar));
        $this->assertSame(1, substr_count($bar, 'class="page-item active"'));
        $this->assertStringContainsString($this->active($page), $bar);

        if ($page < $pages) {
            $this->assertStringContainsString($this->link($page + 1, 'Next'), $bar);
            $this->assertStringContainsString($this->link($pages, 'Last'), $bar);
        } else {
            $this->assertSame(2, substr_count($bar, 'class="page-item disabled"'));
        }
    }

    /**
     * @testdox  getBar() links the first, second and last pages around the middle range
     *
     * @return  void
     */
    public function testGetBarMiddleLinks(): void
    {
        $bar = $this->createPaginationForPage(10, 20)->getBar();

        foreach ([1, 2, 8, 9, 11, 12, 19, 20] as $page) {
            $this->assertStringContainsString($this->link($page), $bar);
        }

        $this->assertStringContainsString('<li class="dot">...</li>', $bar);
        $this->assertStringContainsString('<li class="dot">..</li>', $bar);
    }

    /**
     * @testdox  getBar() keeps the existing query of the URI in the links
     *
     * @return  void
     */
    public function testGetBarKeepsQuery(): void
    {
        $pagination = new TrackerPagination(new Uri(self::BASE_URI . '?sort=title&page=1'));
        $pagination->setValues(30, 0, 10);

        $bar = $pagination->getBar();

        $this->assertStringContainsString('href="' . self::BASE_URI . '?sort=title&amp;page=2">2</a>', $bar);
        $this->assertStringContainsString('href="' . self::BASE_URI . '?sort=title&amp;page=3">Last</a>', $bar);
    }

    /**
     * @testdox  Query values are escaped in the links of the pagination bar
     *
     * @return  void
     */
    public function testGetBarEscapesQueryValues(): void
    {
        $pagination = new TrackerPagination(new Uri(self::BASE_URI . '?q=a"b\'c<d>'));
        $pagination->setValues(30, 0, 10);

        $bar = $pagination->getBar();

        $this->assertStringNotContainsString('a"b', $bar);
        $this->assertStringNotContainsString("b'c", $bar);
        $this->assertStringNotContainsString('<d>', $bar);
        $this->assertStringContainsString('q=a&quot;b&#039;c&lt;d&gt;&amp;page=2', $bar);
    }
}
