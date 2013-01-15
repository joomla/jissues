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
 * HTML Utility class for issues
 *
 * @package     JTracker
 * @subpackage  HTML
 * @since       1.0
 */
class JHtmlIssues
{
	public static function link($id, $closed = false, $text = '')
	{
		$text = ($text) ? : ' #' . $id;

		$link = JHtml::link(JRoute::_('index.php?option=com_tracker&view=issue&id=' . $id), $text);

		return ($closed) ? '<del>' . $link . '</del>' : $link;
	}
}
