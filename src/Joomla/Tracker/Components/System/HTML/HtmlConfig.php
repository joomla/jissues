<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\Components\System\HTML;

/**
 * HTML Helper class to display the configuration
 *
 * @since  1.0
 */
final class HtmlConfig
{
	/**
	 * Display a form field for editing configuration files.
	 *
	 * @param   string  $key    The key.
	 * @param   string  $value  The value.
	 * @param   string  $group  The group.
	 *
	 * @return  string  HTML markup for the selected field
	 *
	 * @since   1.0
	 */
	public static function field($key, $value, $group = '')
	{
		$html = array();

		$name = '';
		$name .= 'config';
		$name .= $group ? '[' . $group . ']' : '';
		$name .= '[' . $key . ']';

		$htmlId = str_replace(array('[', ']'), '', $name);

		$html[] = '<label for="' . $htmlId . '">' . ucfirst($key) . '</label>';
		$html[] = '<input type="text" name="' . $name . '" id="' . $htmlId . '" value="' . $value . '" />';

		return implode("\n", $html);
	}
}
