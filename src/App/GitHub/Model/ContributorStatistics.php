<?php

/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\GitHub\Model;

use Joomla\Github\Package\Repositories\Statistics;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Fetches a repository's contributor statistics from GitHub and caches them.
 *
 * Querying the GitHub API on every view of the statistics page quickly exhausts the API rate limit.
 *
 * @since  1.0
 */
class ContributorStatistics
{
    /**
     * How long (in seconds) contributor statistics are cached.
     *
     * @var    integer
     * @since  1.0
     */
    public const LIFETIME = 3600;

    /**
     * How long (in seconds) an empty result is cached, e.g. while GitHub is still computing the statistics.
     *
     * @var    integer
     * @since  1.0
     */
    public const RETRY_LIFETIME = 60;

    /**
     * Constructor.
     *
     * @param   Statistics              $statistics  The GitHub statistics API.
     * @param   CacheItemPoolInterface  $cache       The cache pool.
     *
     * @since   1.0
     */
    public function __construct(private readonly Statistics $statistics, private readonly CacheItemPoolInterface $cache)
    {
    }

    /**
     * Get the contributor statistics of a repository.
     *
     * @param   string  $owner  The owner of the repository.
     * @param   string  $repo   The repository name.
     *
     * @return  array  The contributor statistics as returned by GitHub, an empty array if they are not available yet.
     *
     * @since   1.0
     * @throws  \RuntimeException  If GitHub returns an error.
     */
    public function getContributors(string $owner, string $repo): array
    {
        $item = $this->cache->getItem($this->getCacheKey($owner, $repo));

        if ($item->isHit()) {
            $data = $item->get();

            return \is_array($data) ? $data : [];
        }

        try {
            $data = $this->statistics->getListContributors($owner, $repo);
        } catch (\DomainException $exception) {
            // GitHub responds with a 202 status while the statistics are being generated, the package throws a DomainException for it
            if ($exception->getCode() !== 202) {
                throw $exception;
            }

            $data = [];
        }

        $data = \is_array($data) ? $data : [];

        // An empty result is only kept briefly, GitHub will have the statistics ready soon
        $item->set($data);
        $item->expiresAfter($data === [] ? self::RETRY_LIFETIME : self::LIFETIME);
        $this->cache->save($item);

        return $data;
    }

    /**
     * Get the cache key for a repository.
     *
     * @param   string  $owner  The owner of the repository.
     * @param   string  $repo   The repository name.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function getCacheKey(string $owner, string $repo): string
    {
        return 'github_stats_contributors_' . md5(strtolower($owner . '/' . $repo));
    }
}
