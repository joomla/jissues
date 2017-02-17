<?php
/**
 * Part of the Joomla Tracker Service Package
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Service;

use Joomla\Cache\AbstractCacheItemPool;
use Joomla\Cache\Adapter as CacheAdapter;
use Joomla\Cache\CacheItemPoolInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Psr\Cache\CacheItemPoolInterface as PsrCacheItemPoolInterface;

/**
 * Cache service provider
 *
 * @since  1.0
 */
class CacheProvider implements ServiceProviderInterface
{
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function register(Container $container)
	{
		$container->alias('cache', PsrCacheItemPoolInterface::class)
			->alias(CacheItemPoolInterface::class, PsrCacheItemPoolInterface::class)
			->alias(AbstractCacheItemPool::class, PsrCacheItemPoolInterface::class)
			->share(
				PsrCacheItemPoolInterface::class,
				function (Container $container)
				{
					/** @var \Joomla\Registry\Registry $config */
					$config = $container->get('config');

					// If caching isn't enabled then just return a void cache
					if (!$config->get('cache.enabled', false))
					{
						return new CacheAdapter\None;
					}

					$adapter = $config->get('cache.adapter', 'file');

					switch ($adapter)
					{
						case 'file':
							$path = $config->get('cache.filesystem.path', 'cache/general');

							// If no path is given, fall back to the system's temporary directory
							if (empty($path))
							{
								$path = sys_get_temp_dir();
							}

							// If the path is relative, make it absolute... Sorry Windows users, this breaks support for your environment
							if (substr($path, 0, 1) !== '/')
							{
								$path = JPATH_ROOT . '/' . $path;
							}

							$options = [
								'file.path' => $path,
							];

							return new CacheAdapter\File($options);

						case 'none':
							return new CacheAdapter\None;

						case 'runtime':
							return new CacheAdapter\Runtime;
					}

					throw new \InvalidArgumentException(sprintf('The "%s" cache adapter is not supported.', $adapter));
				},
				true
			);
	}
}
