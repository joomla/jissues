<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug\Logger;

use Psr\Log\AbstractLogger;

/**
 * Class CallbackLogger.
 *
 * @since  1.0
 */
class CallbackLogger extends AbstractLogger
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
