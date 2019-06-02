<?php
/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig\Service;

use Joomla\Application\AbstractApplication;
use Joomla\Http\Http;
use JTracker\Application;
use JTracker\Authentication\GitHub\GitHubLoginHelper;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Service class rendering the `joomla.org` CDN template elements
 */
class CdnRenderer
{
	/**
	 * Web application
	 *
	 * @var    Application
	 * @since  1.0
	 */
	private $app;

	/**
	 * Cache pool
	 *
	 * @var    CacheItemPoolInterface
	 * @since  1.0
	 */
	private $cache;

	/**
	 * HTTP connector
	 *
	 * @var    Http
	 * @since  1.0
	 */
	private $http;

	/**
	 * Login helper
	 *
	 * @var    GitHubLoginHelper
	 * @since  1.0
	 */
	private $loginHelper;

	/**
	 * Constructor.
	 *
	 * @param   Application             $app          Web application
	 * @param   CacheItemPoolInterface  $cache        Cache pool
	 * @param   Http                    $http         HTTP connector
	 * @param   GitHubLoginHelper       $loginHelper  Login helper
	 *
	 * @since   1.0
	 */
	public function __construct(Application $app, CacheItemPoolInterface $cache, Http $http, GitHubLoginHelper $loginHelper)
	{
		$this->app         = $app;
		$this->cache       = $cache;
		$this->http        = $http;
		$this->loginHelper = $loginHelper;
	}

	/**
	 * Retrieve the template footer contents from the CDN.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getCdnFooter() : string
	{
		$key = md5(__METHOD__ . ($this->app->getUser()->id > 0 ? '_authenticated' : '_guest'));

		$item = $this->cache->getItem($key);

		// Make sure we got a hit on the item, otherwise we'll have to re-cache
		if ($item->isHit())
		{
			$body = $item->get();
		}
		else
		{
			try
			{
				// Set a very short timeout to try and not bring the site down
				$response = $this->http->get('https://cdn.joomla.org/template/renderer.php?section=footer', [], 2);

				if ($response->code !== 200)
				{
					return 'Could not load template section.';
				}

				$body = $response->body;

				// Replace the common placeholders
				$body = strtr(
					$body,
					[
						'%reportroute%'  => $this->app->get('uri.base.path') . 'tracker/jtracker/add',
						'%currentyear%' => date('Y'),
					]
				);

				// Replace the context aware placeholders
				if ($this->app->getUser()->id)
				{
					$body = strtr(
						$body,
						[
							'%loginroute%' => $this->app->get('uri.base.path') . 'logout',
							'%logintext%'  => 'Log out',
						]
					);
				}
				else
				{
					$body = strtr(
						$body,
						[
							'%loginroute%' => $this->loginHelper->getLoginUri(),
							'%logintext%'  => 'Log in',
						]
					);
				}

				$item->set($body);
				$item->expiresAfter(null);

				$this->cache->save($item);
			}
			catch (\RuntimeException $e)
			{
				return 'Could not load template section.';
			}
		}

		return $body;
	}

	/**
	 * Retrieve the template mega menu from the CDN.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getCdnMenu() : string
	{
		$key = md5(__METHOD__);

		$item = $this->cache->getItem($key);

		// Make sure we got a hit on the item, otherwise we'll have to re-cache
		if ($item->isHit())
		{
			$body = $item->get();
		}
		else
		{
			try
			{
				// Set a very short timeout to try and not bring the site down
				$response = $this->http->get('https://cdn.joomla.org/template/renderer.php?section=menu', [], 2);

				if ($response->code !== 200)
				{
					return 'Could not load template section.';
				}

				$body = $response->body;

				// Needless concatenation for PHPCS 150 character line limits...
				$replace = "\t<div id=\"nav-search\" class=\"navbar-search pull-right\">\n\t\t"
					. "<jdoc:include type=\"modules\" name=\"position-0\" style=\"none\" />\n\t</div>\n";

				// Remove the search module
				$body = str_replace($replace, '', $body);

				$item->set($body);
				$item->expiresAfter(null);

				$this->cache->save($item);
			}
			catch (\RuntimeException $e)
			{
				return 'Could not load template section.';
			}
		}

		return $body;
	}
}
