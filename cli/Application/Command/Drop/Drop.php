<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Drop;

use Application\Command\TrackerCommand;

/**
 * Class for dropping stuff.
 *
 * @since  1.0
 */
class Drop extends TrackerCommand
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Drop stuff.';

	/**
	 * Execute the command.
	 *
	 * NOTE: This command must not be executed without parameters !
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$className = join('', array_slice(explode('\\', get_class($this)), -1));

		return $this->displayMissingOption(strtolower($className), __DIR__);
	}
}
