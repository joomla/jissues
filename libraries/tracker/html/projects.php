<?php
/**
 * @package     X
 * @subpackage  X.Y
 *
 * @copyright   Copyright (C) 2012 X. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

/**
 * Utility class for projects
 *
 * @package     X
 * @subpackage  HTML
 * @since       1.0
 */
abstract class JHtmlProjects
{
	/**
	 * Get a select list.
	 *
	 * @param   string  $section   The section
	 * @param   string  $name      Name for the control
	 * @param   string  $selected  The selected field
	 * @param   string  $title     Title to show
	 * @param   string  $js        Javascript
	 *
	 * @return mixed
	 */
	public static function select($section, $name, $selected = '', $title = '', $js = 'onchange="document.adminForm.submit();"')
	{
		$title = $title ? : JText::_('Select an Option');

		$options = JHtml::_('category.options', $section);

		if ($options)
		{
			$options = array_merge(
				array(JHtml::_('select.option', '', $title)),
				$options
			);
		}
		else
		{
			return '';
		}

		return
			JHtml::_(
				'select.genericlist',
				$options,
				'fields[' . $name . ']',
				$js,
				'value', 'text', // Hate it..
				$selected
			);
	}

	/**
	 * Returns a html list of categories for the given extension.
	 *
	 * @param   string  $section   The extension option.
	 * @param   bool    $links     Links or simple list items.
	 * @param   string  $selected  The selected item.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function listing($section = '', $links = false, $selected = '')
	{
		$items = self::items($section);

		if (0 == count($items))
			return '';

		$html = array();

		$link = 'index.php?option=com_categories&extension=%s.%s';

		$html[] = '<ul>';

		foreach ($items as $item)
		{
			$selected = ($selected == $item->id) ? ' selected' : '';
			$repeat = ($item->level - 1 >= 0) ? $item->level - 1 : 0;
			$item->title = str_repeat('- ', $repeat) . $item->title;

			$html[] = '<li>';
			$html[] = $links
				? JHtml::link(sprintf($link, $section, $item->id), $item->title, array('class' => $selected))
				: $item->title;
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		return implode("\n", $html);
	}

	/**
	 * Get the items list.
	 *
	 * @param   string  $section  A section
	 *
	 * @return array
	 */
	public static function items($section)
	{
		static $sections = array();

		if (isset($sections[$section]))
		{
			return $sections[$section];
		}

		$db = JFactory::getDbo();

		$items = $db->setQuery(
			$db->getQuery(true)
				->select('id, title, alias, level, parent_id')
				->from('#__categories')
				->where('parent_id > 0')
				->where('extension = ' . $db->q($section))
				->order('lft')
		)->loadObjectList();

		$sections[$section] = $items;

		return $sections[$section];
	}

}
