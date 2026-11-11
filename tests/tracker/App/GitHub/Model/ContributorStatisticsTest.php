<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\GitHub\Model;

use App\GitHub\Model\ContributorStatistics;
use Joomla\Github\Package\Repositories\Statistics;
use Joomla\Http\Exception\UnexpectedResponseException;
use Joomla\Http\Http;
use Joomla\Http\Response;
use Joomla\Registry\Registry;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Tests for the cached contributor statistics.
 *
 * @since  1.0
 */
class ContributorStatisticsTest extends TestCase
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
     * Create the GitHub statistics API using the given HTTP client.
     *
     * @param   Http  $client  The HTTP client.
     *
     * @return  Statistics
     *
     * @since   1.0
     */
    private function statistics(Http $client): Statistics
    {
        return new Statistics(new Registry(['api' => ['url' => 'https://api.github.com']]), $client);
    }

    /**
     * Get the remaining lifetime of the single item in the cache.
     *
     * @param   ArrayAdapter  $cache  The cache.
     *
     * @return  integer
     *
     * @since   1.0
     */
    private function remainingLifetime(ArrayAdapter $cache): int
    {
        $expiries = (new \ReflectionProperty(ArrayAdapter::class, 'expiries'))->getValue($cache);

        $this->assertCount(1, $expiries);

        return (int) round(reset($expiries) - microtime(true));
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('The statistics are fetched from GitHub once and then served from the cache for an hour')]
    public function testStatisticsAreCached(): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->once())
            ->method('get')
            ->with('https://api.github.com/repos/joomla/joomla-cms/stats/contributors')
            ->willReturn($this->response(200, '[{"total":5,"author":{"login":"alice"}}]'));

        $cache      = new ArrayAdapter();
        $statistics = new ContributorStatistics($this->statistics($client), $cache);

        $first  = $statistics->getContributors('joomla', 'joomla-cms');
        $second = $statistics->getContributors('joomla', 'joomla-cms');

        $this->assertCount(1, $first);
        $this->assertSame('alice', $first[0]->author->login);
        $this->assertEquals($first, $second);
        $this->assertEqualsWithDelta(ContributorStatistics::LIFETIME, $this->remainingLifetime($cache), 2);
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('The statistics are cached per repository')]
    public function testStatisticsAreCachedPerRepository(): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(
                fn ($url): Response => str_contains((string) $url, '/joomla/joomla-cms/')
                    ? $this->response(200, '[{"total":1,"author":{"login":"alice"}}]')
                    : $this->response(200, '[{"total":2,"author":{"login":"bob"}}]')
            );

        $statistics = new ContributorStatistics($this->statistics($client), new ArrayAdapter());

        $this->assertSame('alice', $statistics->getContributors('joomla', 'joomla-cms')[0]->author->login);
        $this->assertSame('bob', $statistics->getContributors('joomla', 'jissues')[0]->author->login);
        $this->assertSame('alice', $statistics->getContributors('joomla', 'joomla-cms')[0]->author->login);
        $this->assertSame('bob', $statistics->getContributors('joomla', 'jissues')[0]->author->login);
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('While GitHub is computing the statistics an empty list is returned and only cached briefly')]
    public function testComputingResponseIsCachedBriefly(): void
    {
        $client = $this->createStub(Http::class);
        $client->method('get')
            ->willReturn($this->response(202, '{}'));

        $cache      = new ArrayAdapter();
        $statistics = new ContributorStatistics($this->statistics($client), $cache);

        $this->assertSame([], $statistics->getContributors('joomla', 'joomla-cms'));
        $this->assertEqualsWithDelta(ContributorStatistics::RETRY_LIFETIME, $this->remainingLifetime($cache), 2);
    }

    /**
     * @return  void
     *
     * @since   1.0
     */
    #[TestDox('Other GitHub errors are passed on and not cached')]
    public function testErrorsAreNotCached(): void
    {
        $client = $this->createMock(Http::class);
        $client->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->response(403, '{"message":"API rate limit exceeded"}'),
                $this->response(200, '[{"total":5,"author":{"login":"alice"}}]')
            );

        $statistics = new ContributorStatistics($this->statistics($client), new ArrayAdapter());

        try {
            $statistics->getContributors('joomla', 'joomla-cms');
            $this->fail('An exception should have been thrown.');
        } catch (UnexpectedResponseException $exception) {
            $this->assertSame(403, $exception->getCode());
        }

        $this->assertCount(1, $statistics->getContributors('joomla', 'joomla-cms'));
    }
}
