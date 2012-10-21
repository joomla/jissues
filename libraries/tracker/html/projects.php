<?php
/**
 * @package     JTracker
 * @subpackage  HTML
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
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
	 * Get a select list.
	 *
	 * @param   string  $section   The section
	 * @param   string  $name      Name for the control
	 * @param   string  $selected  The selected field
	 * @param   string  $title     Title to show
	 * @param   string  $js        Javascript
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public static function select($section, $name, $selected = '', $title = '', $js = 'onchange="document.adminForm.submit();"')
	{
		$title = $title ? : JText::_('Select an Option');

		$options = JHtmlCategory::options($section);

		if ( ! $options)
		{
			return '';
		}

		$options = array_merge(array(JHtmlSelect::option('', $title)), $options);

		return JHtmlSelect::genericlist(
	//		'select.genericlist',
			$options,
			'fields[' . $name . ']',
			$js,
			'value', 'text', // Hate it..
			$selected, 'select-'.$name
		);
	}

	/**
	 * Returns a HTML list of categories for the given extension.
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
	 * @return  array
	 *
	 * @since   1.0
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
				->select('id, title, alias, description, level, parent_id')
				->from('#__categories')
				->where('parent_id > 0')
				->where('extension = ' . $db->q($section))
				->order('lft')
		)->loadObjectList();

		$sections[$section] = $items;

		return $sections[$section];
	}

	/**
	 * Draws a text input.
	 *
	 * @todo moveme
	 *
	 * @param        $name
	 * @param        $value
	 * @param string $description
	 *
	 * @return string
	 */
	public static function textfield($name, $value, $description = '')
	{
		$description = ($description) ? ' class="hasTooltip" title="' . htmlspecialchars($description, ENT_COMPAT, 'UTF-8') . '"' : '';

		return '<input type="text" name="fields[' . $name . ']" '
			. ' id="txt-' . $name . '" value="' . $value . '"' . $description . ' />';
	}

	/**
	 * Draws a checkbox
	 *
	 * @todo     moveme
	 *
	 * @param        $name
	 * @param bool   $checked
	 * @param string $description
	 *
	 * @return string
	 */
	public static function checkbox($name, $checked = false, $description = '')
	{
		$description = ($description) ? ' class="hasTooltip" title="' . htmlspecialchars($description, ENT_COMPAT, 'UTF-8') . '"' : '';
		$checked = $checked ? ' checked="checked"' : '';

		return '<input type="checkbox" name="fields[' . $name . ']" '
			. ' id="chk-' . $name . '"' . $checked . $description . ' />';
	}

}
