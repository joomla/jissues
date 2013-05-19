<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\Debug\Logger;

use Psr\Log\LoggerInterface;

/**
 * Class CallbackLogger.
 *
 * @since  1.0
 */
class CallbackLogger implements LoggerInterface
{
	/**
	 * The callback function
	 * @since  1.0
	 * @var mixed
	 */
	private $callback;

	/**
	 * Constructor.
	 *
	 * @param   mixed  $callback  A valid callback function.
	 *
	 * @throws \RuntimeException
	 */
	public function __construct($callback)
	{
		if (false == is_callable($callback))
		{
			throw new \RuntimeException(__CLASS__ . ' created without a valid callback function.');
		}

		$this->callback = $callback;
	}

	/**
	 * System is unusable.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function emergency($message, array $context = array())
	{
		$this->log('emergency', $message, $context);
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function alert($message, array $context = array())
	{
		$this->log('alert', $message, $context);
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function critical($message, array $context = array())
	{
		$this->log('critical', $message, $context);
	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function error($message, array $context = array())
	{
		$this->log('error', $message, $context);
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function warning($message, array $context = array())
	{
		$this->log('warning', $message, $context);
	}

	/**
	 * Normal but significant events.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function notice($message, array $context = array())
	{
		$this->log('notice', $message, $context);
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function info($message, array $context = array())
	{
		$this->log('info', $message, $context);
	}

	/**
	 * Detailed debug information.
	 *
	 * @param   string  $message  The log message
	 * @param   array   $context  The context
	 *
	 * @since  1.0
	 * @return null
	 */
	public function debug($message, array $context = array())
	{
		$this->log('debug', $message, $context);
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param   mixed   $level    The log level.
	 * @param   string  $message  The message
	 * @param   array   $context  The log context.
	 *
	 * @since  1.0
	 * @return null
	 */
	public function log($level, $message, array $context = array())
	{
		call_user_func($this->callback, $level, $message, $context);
	}
}
