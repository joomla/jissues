<?php
/**
 * @package     JTracker
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

/**
 * HTML Utility class for projects
 *
 * @package     JTracker
 * @subpackage  HTML
 * @since       1.0
 */
abstract class JHtmlProjects
{
	/**
	 * Returns a HTML list of categories for the given extension.
	 *
	 * @param   string   $section    The extension option.
	 * @param   integer  $projectId  The project id.
	 * @param   bool     $links      Links or simple list items.
	 * @param   string   $selected   The selected item.
	 *
	 * @return  string
	 *
	 * @deprecated - used only in backend
	 *
	 * @since      1.0
	 */
	public static function listing($section = '', $projectId = 0, $links = false, $selected = '')
	{
		$items = JHtmlCustomfields::items($section, $projectId);

		if (0 == count($items))
		{
			return '';
		}

		$html = array();

		$link = 'index.php?option=com_categories&extension=%s.%s';

		$html[] = '<ul class="unstyled">';

		foreach ($items as $item)
		{
			$selected    = ($selected == $item->id) ? ' selected' : '';
			$repeat      = ($item->level - 1 >= 0) ? $item->level - 1 : 0;
			$item->title = str_repeat('- ', $repeat) . $item->title;

			$html[] = '<li>';
			$html[] = $links
				? JHtml::link(sprintf($link, 'com_tracker' . ($section ? '.' . $section : ''), $item->id), $item->title, array('class' => $selected))
				: $item->title;
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		return implode("\n", $html);
	}
}
