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
 * Class for running checkstyle tests.
 *
 * @since  1.0
 */
class Copypaste extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Run Copy/Paste Detector (CPD) for PHP code.';

	/**
	 * Execute the command.
	 *
	 * @return  string  Number of errors.
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Run Copy/Paste Detector');

		$application = new \SebastianBergmann\PHPCPD\CLI\Application;

		$application->setAutoExit(false);

		$cloneCount = $application->run(
			new ArrayInput(
				['values' => [
					JPATH_ROOT . '/cli',
					JPATH_ROOT . '/src'
				]]
			)
		);

		$this->out($cloneCount ? sprintf('<error> %d clones found. </error>', $cloneCount) : '<ok>No CP errors</ok>');

		if ($this->exit)
		{
			$this->getApplication()->close($cloneCount ? 1 : 0);
		}

		return $cloneCount;
	}
}
