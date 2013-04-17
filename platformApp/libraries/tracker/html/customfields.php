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
 * HTML Utility class for custom fields.
 *
 * @package     JTracker
 * @subpackage  HTML
 * @since       1.0
 */
abstract class JHtmlCustomfields
{
	/**
	 * Get a select list.
	 *
	 * @param   string   $section    The section
	 * @param   integer  $projectId  The project id.
	 * @param   string   $name       Name for the control
	 * @param   string   $selected   The selected field
	 * @param   string   $title      Title to show
	 * @param   string   $js         Javascript
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function select($section, $projectId, $name, $selected = '', $title = '', $js = '')
	{
		$title = $title ? : JText::_('Select an Option');

		// First check project specific fields
		$options = JHtmlCategory::options(static::getSubSection($section, $projectId));

		if (!$options)
		{
			// Second check global fields
			$options = JHtmlCategory::options(static::getSubSection($section, 0));
		}

		if (!$options)
		{
			// That's bad.
			return 'No fields found for section ' . $section;
		}

		$options = array_merge(array(JHtmlSelect::option('', $title)), $options);

		return JHtmlSelect::genericlist(
			$options,
			'jform[fields][selects][' . $name . ']',
			$js,
			'value',
			'text',
			$selected,
			'select-' . $name
		);
	}

	/**
	 * Get the items list.
	 *
	 * @param   string   $section    A section
	 * @param   integer  $projectId  The project id.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function items($section, $projectId)
	{
		static $items = array();

		if (isset($items[$section]))
		{
			return $items[$section];
		}

		// First check project specific fields
		$check = static::getItems($section, $projectId);

		if (!$check)
		{
			// Second check global fields
			$check = static::getItems($section, null);
		}

		if (!$check)
		{
			// That's bad.
			$items[$section] = array();
		}
		else
		{
			$items[$section] = $check;
		}

		return $items[$section];
	}

	/**
	 * Generates a text input.
	 *
	 * @param   string   $name         Name for the HTML element
	 * @param   boolean  $value        Actual value.
	 * @param   string   $description  Description text.
	 *
	 * @return string
	 */
	public static function textfield($name, $value, $description = '')
	{
		$description = ($description) ? ' class="hasTooltip" title="' . htmlspecialchars($description, ENT_COMPAT, 'UTF-8') . '"' : '';

		return '<input type="text" name="jform[fields][textfields][' . $name . ']" '
			. ' id="txt-' . $name . '" value="' . $value . '"' . $description . ' />';
	}

	/**
	 * Generates a checkbox.
	 *
	 * @param   string   $name         Name for the HTML element
	 * @param   boolean  $checked      Actual state.
	 * @param   string   $description  Description text.
	 *
	 * @return string
	 */
	public static function checkbox($name, $checked = false, $description = '')
	{
		$description = ($description) ? ' class="hasTooltip" title="' . htmlspecialchars($description, ENT_COMPAT, 'UTF-8') . '"' : '';
		$checked = $checked ? ' checked="checked"' : '';

		return '<input type="checkbox" name="jform[fields][checkboxes][' . $name . ']" '
			. ' id="chk-' . $name . '"' . $checked . $description . ' />';
	}

	/**
	 * Get the items list.
	 *
	 * @param   string   $section    A section
	 * @param   integer  $projectId  The project id.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public static function getItems($section, $projectId)
	{
		static $items = array();

		$subSection = static::getSubSection($section, $projectId);

		if (isset($items[$subSection]))
		{
			return $items[$subSection];
		}

		$db = JFactory::getDbo();

		$items[$subSection] = $db->setQuery(
			$db->getQuery(true)
				->select('id, title, alias, description, level, parent_id')
				->from('#__categories')
				->where('parent_id > 0')
				->where('extension = ' . $db->q($subSection))
				->order('lft')
		)->loadObjectList();

		return $items[$subSection];
	}

	/**
	 * Get a sub section according to the project id.
	 *
	 * @param   string   $section    Section name.
	 * @param   integer  $projectId  The project id.
	 *
	 * @return string
	 */
	private static function getSubSection($section, $projectId = 0)
	{
		$subSection = 'com_tracker';

		$subSection .= ($projectId) ? '.' . $projectId : '';
		$subSection .= ($section) ? '.' . $section : '';

		return $subSection;
	}

}
