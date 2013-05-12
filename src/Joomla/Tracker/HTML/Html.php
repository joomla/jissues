<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\HTML;

use Joomla\Utilities\ArrayHelper;

/**
 * Class Html.
 *
 * !!
 * !! NOTE: This class *must* only contain "dumb", HTML producing, functions !!
 * !!
 *
 * @since  1.0
 */
class Html
{
	/**
	 * Write a <a></a> element
	 *
	 * @param   string  $url         The relative URL to use for the href attribute
	 * @param   string  $text        The target attribute to use
	 * @param   array   $attributes  An associative array of attributes to add
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function link($url, $text, $attributes = null)
	{
		if (is_array($attributes))
		{
			$attributes = ArrayHelper::toString($attributes);
		}

		return '<a href="' . $url . '" ' . $attributes . '>' . $text . '</a>';
	}
}
