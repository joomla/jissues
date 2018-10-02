<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use PHP_CodeSniffer_CLI;

/**
 * Class for running checkstyle tests.
 *
 * @since  1.0
 */
class Checkstyle extends Test
{
	/**
	 * Allowed codestyle failures, generally because of issues with the sniffer
	 *
	 * @var    integer
	 * @since  1.0
	 */
	const ALLOWED_FAIL_COUNT = 0;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Run PHP CodeSniffer tests.');
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
		$this->getApplication()->outputTitle(g11n3t('Test Checkstyle'));

		// Make sure coding standards are registered
		$this->execCommand('cd ' . JPATH_ROOT . ' && vendor/bin/phpcs --config-set installed_paths vendor/joomla/coding-standards 2>&1');

		$options = [];

		$options['files'] = [
			JPATH_ROOT . '/cli',
			JPATH_ROOT . '/src',
		];

		$options['standard'] = [JPATH_ROOT . '/ruleset.xml'];

		$options['showProgress'] = true;

		$phpCs = new PHP_CodeSniffer_CLI;
		$phpCs->checkRequirements();

		$numErrors = $phpCs->process($options);

		$this
			->out()
			->out(
				$numErrors > self::ALLOWED_FAIL_COUNT
				? sprintf('<error> %s </error>', sprintf(g11n4t('Finished with one error', 'Finished with %d errors', $numErrors), $numErrors))
				: sprintf('<ok>%s</ok>', g11n3t('Success'))
			);

		if ($this->exit)
		{
			exit($numErrors > self::ALLOWED_FAIL_COUNT ? 1 : 0);
		}

		return $numErrors;
	}
}
