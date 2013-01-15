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
 * HTML Utility class for relations
 *
 * @package     JTracker
 * @subpackage  HTML
 * @since       1.0
 */
class JHtmlRelations
{
	protected static $types = array();

	public static function input($type = null, $id = null)
	{
		self::_load();

		$html = array();

		$html[] = '<select name="jform[rel_type]" class="inputbox span5" id="jform_reltype">'
			. JHtmlSelect::options(self::$types, 'value', 'text', $type)
			. '</select>';

//		$html[] = '<div class="input-prepend">';
//		$html[] = '<span class="add-on">#</span>';
		$html[] = '# <input name="jform[rel_id]" type="text" class="input-small"'
			. ' value="' . ($id ? $id : '') . '" placeholder="' . JText::_('Issue no.') . '"/>';

//		$html[] = '</div>';

		return implode("\n", $html);
	}


	public static function types()
	{
		self::_load();

	}

	private static function _load()
	{

		$db = JFactory::getDbo();

		$items = $db->setQuery(
			$db->getQuery(true)
				->from($db->qn('#__issues_relations_types'))
				->select($db->qn(array('id', 'name')))
		)->loadObjectList();

		self::$types[] = JHtmlSelect::option(0, JText::_('COM_TRACKER_RELTYPE'));

		foreach ($items as $item)
		{
			self::$types[] = JHtmlSelect::option($item->id, JText::_('COM_TRACKER_RELTYPE_' . strtoupper($item->name)));
		}

	}
}
