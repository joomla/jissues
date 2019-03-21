<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class for clearing the Twig cache.
 *
 * @since  1.0
 */
class Twig extends Clear
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Clear the Twig cache.';
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Clear Twig Cache Directory');

		if (!$this->getApplication()->get('renderer.cache', false))
		{
			$this->out('<info>Twig caching is not enabled.</info>');

			return;
		}

		$cacheDir     = JPATH_ROOT . '/cache';
		$twigCacheDir = $this->getApplication()->get('renderer.cache');

		$this->logOut(sprintf('Cleaning the cache dir in "%s"', $cacheDir . '/' . $twigCacheDir));

		$filesystem = new Filesystem(new Local($cacheDir));

		if ($filesystem->has($twigCacheDir))
		{
			$filesystem->deleteDir($twigCacheDir);
		}

		$this->out()
			->out('<ok>The Twig cache directory has been cleared.</ok>');
	}
}
