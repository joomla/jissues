<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

/**
 * Class for clearing all cache stores.
 *
 * @since  1.0
 */
class Allcache extends Clear
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Clear all cache stores.');
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
		$this->getApplication()->outputTitle(g11n3t('Clearing All Cache Stores'));

		(new Cache)
			->setContainer($this->getContainer())
			->execute();

		(new Twig)
			->setContainer($this->getContainer())
			->execute();

		$this->out()
			->out('<ok>' . g11n3t('All cache stores have been cleared.') . '</ok>');
	}
}
