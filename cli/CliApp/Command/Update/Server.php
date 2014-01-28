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
	 * @throws \RuntimeException
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Update Server');

		$this->logOut('Beginning git update');

		$output = system('cd ' . JPATH_ROOT . ' && git pull 2>&1', $status);

		if ($status)
		{
			// Command exited with a status != 0
			if ($output)
			{
				$this->logOut($output);

				throw new \RuntimeException($output);
			}

			$this->logOut('An unknown error occurred');

			throw new \RuntimeException('An unknown error occurred');
		}

		$this->logOut('Git update Finished');

		with(new Repoinfo)
			->setContainer($this->getContainer())
			->execute();

		$this->logOut('Update Finished');
	}
}
