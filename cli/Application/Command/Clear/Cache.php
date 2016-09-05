<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Class for clearing the application cache.
 *
 * @since  1.0
 */
class Cache extends Clear
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Clear the application cache.');
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
		$this->getApplication()->outputTitle(g11n3t('Clear Application Cache'));

		/** @var CacheItemPoolInterface $cache */
		$cache = $this->getContainer()->get('cache');

		if ($cache->clear())
		{
			$this->out('<ok>' . g11n3t('The application cache has been cleared.') . '</ok>');
		}
		else
		{
			$this->out('<error>' . g11n3t('There was an error clearing the application cache.') . '</error>');
		}
	}
}
