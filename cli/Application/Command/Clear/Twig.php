<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use Joomla\Filesystem\Folder;

/**
 * Class for clearing the Twig cache.
 *
 * @since  1.0
 */
class Twig extends Clear
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Clear the Twig cache.';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Clear Twig cache dir');

		if (!$this->getApplication()->get('renderer.cache', false))
		{
			$this->out('Twig caching is not enabled.');

			return;
		}

		$cacheDir = JPATH_ROOT . '/' . $this->getApplication()->get('renderer.cache');

		$this->logOut(sprintf('Cleaning the cache dir in "%s"', $cacheDir));

		if (is_dir($cacheDir))
		{
			Folder::delete($cacheDir);
		}

		$this->out()
			->out('The Twig cache directory has been cleared.');
	}
}
