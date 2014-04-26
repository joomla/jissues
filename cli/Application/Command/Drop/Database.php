<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Drop;

use Application\Command\TrackerCommandOption;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class for creating database tables.
 *
 * @since  1.0
 */
class Database extends Drop
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Drop the database tables.';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this
			->addOption(
				new TrackerCommandOption(
					'force', '',
					'Force the execution.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'dump-sql', '',
					'Dump the SQL to be executed.'
				)
			);
	}

	/**
	 * Execute the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Drop Database');

		/* @type \Joomla\Input\Input $input */
		$input = $this->getContainer()->get('app')->input;

		return $this->getContainer()->get('DoctrineRunner')->run(
			new ArrayInput(
				[
					'command'    => 'orm:schema-tool:drop',
					'--dump-sql' => $input->get('dump-sql') ? true : false,
					'--force'    => $input->get('force')    ? true : false
				]
			)
		);
	}
}
