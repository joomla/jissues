<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use SebastianBergmann\PHPLOC\CLI\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Class for running checkstyle tests.
 *
 * @since  1.0
 */
class Copypaste extends Test
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Run Copy/Paste Detector (CPD) for PHP code.');
	}

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
		$this->getApplication()->outputTitle(g11n3t('Run Copy/Paste Detector'));

		$application = new Application;

		$application->setAutoExit(false);

		$cloneCount = $application->run(
			new ArrayInput(
				['values' => [
					JPATH_ROOT . '/cli',
					JPATH_ROOT . '/src'
				]]
			)
		);

		$this->out(
			$cloneCount
				? sprintf('<error> %d clones found. </error>', $cloneCount)
				: '<ok>No CP errors</ok>'
		);

		if ($this->exit)
		{
			exit($cloneCount ? 1 : 0);
		}

		return $cloneCount;
	}
}
