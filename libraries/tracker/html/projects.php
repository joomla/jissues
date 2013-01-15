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
	 * Get a project selector.
	 *
	 * @param   string  $selected  The selected entry.
	 * @param   string  $toolTip   The text for the tooltip.
	 * @param   string  $js        Javascript.
	 *
	 * @return string
	 */
	public static function select($selected = '', $toolTip = '', $js = '')
	{
		$projects = self::projects();

		if (!$projects)
		{
			return '';
		}

		$options = array();
		$html    = array();

		$options[] = JHtmlSelect::option('', JText::_('Select a Project'));

		foreach ($projects as $project)
		{
			$options[] = JHtmlSelect::option($project->project_id, $project->title);
		}

		$input = JHtmlSelect::genericlist($options, 'project_id', $js, 'value', 'text', $selected, 'select-project');

		if ($toolTip)
		{
			$html[] = '<div class="input-append">';
			$html[] = $input;
			$html[] = '<span class="add-on hasTooltip" data-placement="right" style="cursor: help;" title="'
				. htmlspecialchars($toolTip, ENT_COMPAT, 'UTF-8') . '">?</span>';
			$html[] = '</div>';
		}
		else
		{
			$html[] = $input;
		}

		return implode("\n", $html);
	}

	/**
	 * Get the defined projects.
	 *
	 * @todo move to a model.
	 *
	 * @return array
	 */
	public static function projects()
	{
		static $projects = null;

		if ($projects)
		{
			return $projects;
		}

		$db = JFactory::getDbo();

		$projects = $db->setQuery(
			$db->getQuery(true)
				->from('#__tracker_projects')
				->select('*')
		)->loadObjectList();

		if(false == is_array($projects))
		{
			$projects = array();
		}

		return $projects;
	}


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
