<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Application\Command\Test;

use PHPUnit_TextUI_Command;

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

		$this->description = g11n3t('Run PHPUnit tests.');
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
		$this->getApplication()->outputTitle(g11n3t('Test PHPUnit'));

		$command = new PHPUnit_TextUI_Command;

		$options = [
			'--configuration=' . JPATH_ROOT . '/phpunit.xml'
		];

		$returnVal = $command->run($options, false);

		$this
			->out()
			->out(
			$returnVal
				? '<error>' . g11n3t('Finished with errors.') . '</error>'
				: '<ok>' . g11n3t('Success') . '</ok>'
		);

		if ($this->exit)
		{
			exit($returnVal ? 1 : 0);
		}

		return $returnVal;
	}
}
