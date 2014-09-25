<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use g11n\g11n;
use g11n\Support\ExtensionHelper;

/**
 * Class for generating language template files.
 *
 * @since  1.0
 */
class Cache extends Clear
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Clear the g11n language directory.';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Clear g11n cache dir');

		$this->logOut(sprintf('Cleaning the cache dir in "%s"', ExtensionHelper::getCacheDir()));

		g11n::cleanCache();

		$this->out()
			->out('The g11n cache directory has been cleared.');
	}
}
