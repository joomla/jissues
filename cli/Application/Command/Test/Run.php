<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Application\Command\Test;

/**
 * Class for running a test suite.
 *
 * @since  1.0
 */
class Run extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Run all tests';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Test Suite');

		with(new Checkstyle)
			->setContainer($this->getContainer())
			->execute();

		with(new Phpunit)
			->setContainer($this->getContainer())
			->execute();

		$this
			->out()
			->out('Finished =;)');
	}
}
