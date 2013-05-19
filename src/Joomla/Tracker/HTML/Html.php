<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\HTML;

use Joomla\Utilities\ArrayHelper;

/**
 * HTML Renderer class
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
	 * Write a <img></img> element
	 *
	 * @param   string  $file        The URL to use for the src attribute
	 * @param   string  $alt         The alt text
	 * @param   mixed   $attributes  String or associative array of attribute(s) to use
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function image($file, $alt, $attributes = null)
	{
		if (is_array($attributes))
		{
			$attributes = ArrayHelper::toString($attributes);
		}

		return '<img src="' . $file . '" alt="' . $alt . '" ' . $attributes . ' />';
	}

	/**
	 * Write a <a></a> element
	 *
	 * @param   string  $url         The relative URL to use for the href attribute
	 * @param   string  $text        The text for the link
	 * @param   mixed   $attributes  String or associative array of attribute(s) to use
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
