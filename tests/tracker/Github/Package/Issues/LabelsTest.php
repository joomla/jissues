<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Github\Package\Issues;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Registry\Registry;
use JTracker\Github\Package\Issues;
use JTracker\Github\Package\Issues\Labels;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the paginated GitHub labels API.
 *
 * @since  1.0
 */
class LabelsTest extends TestCase
{
    /**
     * Create a response.
     *
     * @param   integer  $code  The status code.
     * @param   string   $body  The body.
     *
     * @return  Response
     *
     * @since   1.0
     */
    private function response(int $code, string $body): Response
    {
        $response = new Response('php://memory', $code);
        $response->getBody()->write($body);

        return $response;
    }

    /**
     * The page and limit are passed to GitHub.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetListRequestsTheGivenPage(): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->once())
            ->method('get')
            ->with('https://api.github.com/repos/joomla/joomla-cms/labels?page=2&per_page=100')
            ->willReturn($this->response(200, '[{"name":"bug","color":"ff0000"}]'));

        $labels = new Labels(new Registry(['api' => ['url' => 'https://api.github.com']]), $client);

        $result = $labels->getList('joomla', 'joomla-cms', 2, 100);

        $this->assertCount(1, $result);
        $this->assertSame('bug', $result[0]->name);
    }

    /**
     * Without page and limit no pagination parameters are sent.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testGetListWithoutPagination(): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->once())
            ->method('get')
            ->with('https://api.github.com/repos/joomla/joomla-cms/labels')
            ->willReturn($this->response(200, '[]'));

        $labels = new Labels(new Registry(['api' => ['url' => 'https://api.github.com']]), $client);

        $this->assertSame([], $labels->getList('joomla', 'joomla-cms'));
    }

    /**
     * The issues package resolves the labels API to the paginated class.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testIssuesPackageUsesPaginatedLabels(): void
    {
        $issues = new Issues(new Registry(), $this->createStub(Http::class));

        // Package::__get() caches the API object in a dynamic property (deprecated as of PHP 8.2)
        $this->assertInstanceOf(Labels::class, @$issues->labels);
    }
}
