<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class for testing the database.
 *
 * @since  1.0
 */
class Database extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Get information about database entities.';

	/**
	 * Execute the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$doctrineRunner = $this->getContainer()->get('DoctrineRunner');

		$this->getApplication()->outputTitle('Database Info');
		$doctrineRunner->run(new ArrayInput(['command' => 'orm:info']));

		$this->getApplication()->outputTitle('Database Validate');
		$doctrineRunner->run(new ArrayInput(['command' => 'orm:validate-schema']));
	}
}
