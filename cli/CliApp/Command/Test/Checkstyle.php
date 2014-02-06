<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Test;

use PHP_CodeSniffer_CLI;

/**
 * Class for running checkstyle tests.
 *
 * @since  1.0
 */
class Checkstyle extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Run PHP CodeSniffer tests';

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
		$this->getApplication()->outputTitle('Test Checkstyle');

		$options = array();

		$options['files'] = array(
			JPATH_ROOT . '/cli',
			JPATH_ROOT . '/src'
		);

		$options['standard'] = array(JPATH_ROOT . '/build/phpcs/Joomla');

		/*
		$options['verbosity']       = 0;
		$options['interactive']     = false;
		$options['explain']         = false;
		$options['local']           = false;
		$options['showSources']     = false;
		$options['extensions']      = array();
		$options['sniffs']          = array();
		$options['ignored']         = array();

		$options['reports'][$reportFormat] = null;
		$options['reportFile']      = null;

		$options['generator']       = '';
		$options['reports']         = array();
		$options['errorSeverity']   = null;
		$options['warningSeverity'] = null;

		$options['tabWidth'] = 0;
		$options['errorSeverity']   = 0;
		$options['encoding'] = 'iso-8859-1';
		$options['warningSeverity'] = 0;
		$options['reportWidth'] = 80;
		*/

		$options['showProgress'] = true;

		$phpcs = new PHP_CodeSniffer_CLI;
		$phpcs->checkRequirements();

		$numErrors = $phpcs->process($options);

		$this
			->out()
			->out(
			$numErrors
				? sprintf('<error> Finished with %d errors </error>', $numErrors)
				: '<ok>Success</ok>'
		);

		if ($this->exit)
		{
			exit($numErrors ? 1 : 0);
		}

		return $numErrors;
	}
}
