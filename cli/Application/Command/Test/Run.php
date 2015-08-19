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

		// TODO - To be consistent with the abstract command's definition, execute() should have no return and the error count stored elsewhere
		$statusCS = (new Checkstyle)
			->setContainer($this->getContainer())
			->setExit(false)
			->execute();

		$statusUT = (new Phpunit)
			->setContainer($this->getContainer())
			->setExit(false)
			->execute();

		$statusLang = (new Langfiles)
			->setContainer($this->getContainer())
			->setExit(false)
			->execute();

		/*
		 * @todo enable when https://github.com/sebastianbergmann/phpcpd/pull/93 is merged
		$statusCPD = (new Copypaste)
			->setContainer($this->getContainer())
			->setExit(false)
			->execute();

		 */

		$status = ($statusCS || $statusUT) ? 1 : 0;

		$this->out()
			->out($status ? '<error> Test Suite Finished with errors </error>.' : '<ok>Test Suite Finished.</ok>');

		$this->getApplication()->close($status);
	}
}
