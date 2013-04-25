#!/usr/bin/env php
<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp;

$loader = require '../vendor/autoload.php';
$loader->add('CliApp', __DIR__);

// @todo used by JFactory::getConfig() and getDbo()
//define('JPATH_FRAMEWORK', 'dooo');

use CliApp\Command\TrackerCommand;
use Joomla\Application\AbstractCliApplication;
use Joomla\Factory;
use Joomla\Input;
use Joomla\Registry\Registry;

use CliApp\Exception\AbortException;

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

/**
 * Simple Installer.
 *
 * @package     JTracker
 * @subpackage  CLI
 * @since       1.0
 */
class TrackerApplication extends AbstractCliApplication
{
	/**
	 * Execute the application.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		include '../etc/configuration.php';

		$config = new \JConfig;

		$this->config->loadObject($config);

		Factory::$config      = $this->config;
		Factory::$application = $this;

		parent::execute();
	}

	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @throws \RuntimeException
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
		$args = $this->input->args;


		if (!$args)
		{
			$command = 'help';
			$action = 'help';
		}
		else
		{
			$command = $args[0];

			$action = (isset($args[1])) ? $args[1] : $command;
		}

		$className = 'CliApp\\Command\\' . ucfirst($command) . '\\' . ucfirst($action);

		if (false == class_exists($className))
		{
			throw new \RuntimeException('Missing class: ' . $className);
		}

		if (false == method_exists($className, 'execute'))
		{
			throw new \RuntimeException(sprintf('Missing method %1$s::%2$s', $className, 'execute'));
		}

		/* @var TrackerCommand $class */
		$class = new $className($this);

		$class->execute();
	}

	/**
	 * This is a useless legacy function.
	 *
	 * @todo remove
	 *
	 * @return string
	 */
	public function getUserStateFromRequest()
	{
		return '';
	}
}

/*
 * Main
 */
try
{
	$app = new TrackerApplication;
	$app->execute();
}
catch (AbortException $e)
{
	echo "\nProcess aborted.\n";

	exit(0);
}
catch (\Exception $e)
{
	echo "\nERROR: " . $e->getMessage() . "\n\n";

	echo $e->getTraceAsString();

	exit($e->getCode() ? : 1);
}

