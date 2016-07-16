<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use g11n\Support\ExtensionHelper;

/**
 * Class for generating language template files.
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

		$this->description = g11n3t('Clear the g11n language directory.');
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
		$this->getApplication()->outputTitle(g11n3t('Clear g11n Cache Directory'));

		$this->logOut(sprintf('Cleaning the cache dir in "%s"', ExtensionHelper::getCacheDir()));

		ExtensionHelper::cleanCache();

		$this->out()
			->out('<ok>' . g11n3t('The g11n cache directory has been cleared.') . '</ok>');
	}
}
