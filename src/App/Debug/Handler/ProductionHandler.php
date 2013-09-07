<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug\Handler;

use \Whoops\Handler\Handler;

/**
 * Catches the Whoops! and simply displays the message.
 *
 * @since  1.0
 */
class ProductionHandler extends Handler
{
	/**
	 * Handle the Whoops!
	 *
	 * @since  1.0
	 * @return integer
	 */
	public function handle()
	{
		echo $this->getException()->getMessage();

		return Handler::QUIT;
	}
}
