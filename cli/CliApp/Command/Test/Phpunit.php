<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Test;

use PHPUnit_TextUI_Command;

/**
 * Class for running PHPUnit tests.
 *
 * @since  1.0
 */
class Phpunit extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Run PHPUnit tests';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Test PHPUnit');

		$command = new PHPUnit_TextUI_Command;

		$options = array(
			' --configuration ' . JPATH_ROOT . '/phpunit.travis.xml'
		);

		$command->run($options, false);

		$this
			->out()
			->out('Finished.');
	}
}
