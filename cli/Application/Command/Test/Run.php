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
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Run all tests';
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
		$this->getApplication()->outputTitle('Test Suite');

		$statusCS = (new Checkstyle)
			->setContainer($this->getContainer())
			->setExit(false)
			->execute();

		$statusUT = (new Phpunit)
			->setContainer($this->getContainer())
			->setExit(false)
			->execute();

		$status = ($statusCS > Checkstyle::ALLOWED_FAIL_COUNT || $statusUT) ? 1 : 0;

		$this
			->out()
			->out(
				$status
					? '<error>Test Suite Finished with errors.</error>'
					: '<ok>Test Suite Finished.</ok>'
			);

		exit($status);
	}
}
