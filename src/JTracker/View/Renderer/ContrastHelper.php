<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View\Renderer;

/**
 * Helper class for determining color contrasts
 *
 * @since  1.0
 */
class ContrastHelper
{
	/**
	 * Get a contrasting color (black or white).
	 *
	 * @param   string  $hexColor  The hex color.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 * @link    https://24ways.org/2010/calculating-color-contrast/
	 */
	public static function getContrastColor($hexColor): string
	{
		$r   = hexdec(substr($hexColor, 0, 2));
		$g   = hexdec(substr($hexColor, 2, 2));
		$b   = hexdec(substr($hexColor, 4, 2));
		$yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return $yiq >= 128 ? 'black' : 'white';
	}
}
