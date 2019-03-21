<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Application\Command\Test;

use PHPUnit\TextUI\Command;

/**
 * Class for running PHPUnit tests.
 *
 * @since  1.0
 */
class Phpunit extends Test
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Run PHPUnit tests.';
	}

	/**
	 * Execute the command.
	 *
	 * @return  integer  PHPUnit_TextUI_TestRunner exit status.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Test PHPUnit');

		$command = new Command;

		$options = [
			'--configuration=' . JPATH_ROOT . '/phpunit.xml',
		];

		$returnVal = $command->run($options, false);

		$this
			->out()
			->out($returnVal ? '<error>Finished with errors.</error>' : '<ok>Success</ok>');

		if ($this->exit)
		{
			exit($returnVal ? 1 : 0);
		}

		return $returnVal;
	}
}
