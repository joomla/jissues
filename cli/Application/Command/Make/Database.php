<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class for creating database tables.
 *
 * @since  1.0
 */
class Database extends Make
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Create the database tables.';

	/**
	 * Execute the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Create Database');

		/* @type \Joomla\Input\Input $input */
		$input = $this->getContainer()->get('app')->input;

		return $this->getContainer()->get('DoctrineRunner')->run(
			new ArrayInput(
				[
					'command' => 'orm:schema-tool:create',
					'--dump-sql' => $input->get('dump-sql') ? true : false,
				]
			)
		);
	}
}
