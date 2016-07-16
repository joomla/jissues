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
 * Class for running phploc tests.
 *
 * @since  1.0
 */
class Phploc extends Test
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Run Lines Of Code (LOC) for PHP code.');
	}

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
		$this->getApplication()->outputTitle(g11n3t('Run PHP Lines Of Code'));

		$application = new Application;

		$application->setAutoExit(false);

		$application->run(
			new ArrayInput(
				[
					'values' => [
					JPATH_ROOT . '/cli',
					JPATH_ROOT . '/src'
					],
				]
			)
		);

		$this->out(g11n3t('Finished.'));

		return $this;
	}
}
