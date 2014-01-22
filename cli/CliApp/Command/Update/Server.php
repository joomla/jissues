<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Update;

use CliApp\Command\Make\Repoinfo;

/**
 * Class for synchronizing a server with the primary git repository
 *
 * @since  1.0
 */
class Server extends Update
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Update the server with the latest git commits';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Update Server');

		$this->logOut('Beginning git update');

		system('git pull');

		with(new Repoinfo)
			->setContainer($this->getContainer())
			->execute();

		$this->logOut('Update Finished');
	}
}
