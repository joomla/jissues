<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Debug\Format\Html;

/**
 * Class LinkFormat
 *
 * @since  1.0
 */
class LinkFormat
{
	/**
	 * The format used to format links.
	 *
	 * @var    string
	 * @since  1.0
	 * @see    http://xdebug.org/docs/all_settings#file_link_format
	 */
	private $linkFormat;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->linkFormat = ini_get('xdebug.file_link_format');
	}

	/**
	 * Format a link.
	 *
	 * @param   string  $file  The file.
	 * @param   string  $line  The line number.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function formatLink($file, $line = '')
	{
		$link = basename($file);
		$link .= ($line) ? ':' . $line : '';

		if ($this->linkFormat)
		{
			$href = $this->linkFormat;
			$href = str_replace('%f', $file, $href);
			$href = str_replace('%l', $line, $href);

			$html = '<a href="' . $href . '">' . $link . '</a>';
		}
		else
		{
			$html = str_replace(JPATH_ROOT, 'JROOT', $file);
		}

		return $html;
	}
}
