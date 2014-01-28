<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command;

/**
 * Class TrackerCommandOption.
 *
 * @since  1.0
 */
class TrackerCommandOption
{
	/**
	 * Long argument
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $longArg = '';

	/**
	 * Short argument
	 *
	 * @var    string
	 * @since  1.0
	 */
	public $shortArg = '';

	/**
	 * Description argument
	 *
	 * @var    string
	 * @since  1.0
	 */
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
