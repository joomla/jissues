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
 * Class for running phploc tests.
 *
 * @since  1.0
 */
class Phploc extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Run Lines Of Code (LOC) for PHP code.';

	/**
	 * Execute the command.
	 *
	 * @return  $this.
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Run PHP Lines Of Code');

		$application = new \SebastianBergmann\PHPLOC\CLI\Application;

		$application->setAutoExit(false);

		$application->run(
			new ArrayInput(
				[
					'values' => [
					JPATH_ROOT . '/cli',
					JPATH_ROOT . '/src'
					],
					/*
					'--log-csv' => 'lllog.csv',
					'--progress' => '1',
					'--git-repository' => '.'
					*/
				]
			)
		);

		$this->out('Finished');

		return $this;
	}
}
