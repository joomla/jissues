<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command;

/**
 * Class TrackerCommandOption.
 *
 * @since  1.0
 */
class TrackerCommandOption
{
	public $longArg = '';

	public $shortArg = '';

	public $description = '';

	/**
	 * Constructor.
	 *
	 * @param   string  $longArg      Long argument.
	 * @param   string  $shortArg     Short argument.
	 * @param   string  $description  Description
	 */
	public function __construct($longArg, $shortArg, $description)
	{
		$this->longArg     = $longArg;
		$this->shortArg    = $shortArg;
		$this->description = $description;
	}
}
