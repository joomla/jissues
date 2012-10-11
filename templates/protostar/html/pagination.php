<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  Templates.protostar
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * This is a file to add template specific chrome to pagination rendering.
 *
 * pagination_list_footer
 *     Input variable $list is an array with offsets:
 *         $list[limit]        : int
 *         $list[limitstart]    : int
 *         $list[total]        : int
 *         $list[limitfield]    : string
 *         $list[pagescounter]    : string
 *         $list[pageslinks]    : string
 *
 * pagination_list_render
 *     Input variable $list is an array with offsets:
 *         $list[all]
 *             [data]        : string
 *             [active]    : boolean
 *         $list[start]
 *             [data]        : string
 *             [active]    : boolean
 *         $list[previous]
 *             [data]        : string
 *             [active]    : boolean
 *         $list[next]
 *             [data]        : string
 *             [active]    : boolean
 *         $list[end]
 *             [data]        : string
 *             [active]    : boolean
 *         $list[pages]
 *             [{PAGE}][data]        : string
 *             [{PAGE}][active]    : boolean
 *
 * pagination_item_active
 *     Input variable $item is an object with fields:
 *         $item->base    : integer
 *         $item->link    : string
 *         $item->text    : string
 *
 * pagination_item_inactive
 *     Input variable $item is an object with fields:
 *         $item->base    : integer
 *         $item->link    : string
 *         $item->text    : string
 *
 * This gives template designers ultimate control over how pagination is rendered.
 *
 * NOTE: If you override pagination_item_active OR pagination_item_inactive you MUST override them both
 */

/**
 * Renders the pagination footer
 *
 * @param   array  $list  Array containing pagination footer
 *
 * @return  string  HTML markup for the full pagination footer
 *
 * @since   3.0
 */
function pagination_list_footer($list)
{
	$html = "<div class=\"pagination\">\n";
	$html .= $list['pageslinks'];
	//$html .= "\n<input type=\"hidden\" name=\"" . $list['prefix'] . "limitstart\" value=\"" . $list['limitstart'] . "\" />";
	$html .= "\n</div>";

	return $html;
}

/**
 * Renders the pagination list
 *
 * @param   array  $list  Array containing pagination information
 *
 * @return  string  HTML markup for the full pagination object
 *
 * @since   3.0
 */
function pagination_list_render($list)
{
	// Calculate to display range of pages
	$currentPage = 1;
	$range = 1;
	$step = 5;
	foreach ($list['pages'] as $k => $page)
	{
		if (!$page['active'])
		{
			$currentPage = $k;
		}
	}
	if ($currentPage >= $step)
	{
		if ($currentPage % $step == 0)
		{
			$range = ceil($currentPage / $step) + 1;
		}
		else
		{
			$range = ceil($currentPage / $step);
		}
	}

	$html = '<div class="btn-group">';
	$html .= $list['start']['data'];
	$html .= $list['previous']['data'];

	foreach ($list['pages'] as $page)
	{
		$html .= $page['data'];
	}

	$html .= $list['next']['data'];
	$html .= $list['end']['data'];

	$html .= '</div>';
	return $html;
}

/**
 * Renders an active item in the pagination block
 *
 * @param   JPaginationObject  $item  The current pagination object
 *
 * @return  string  HTML markup for active item
 *
 * @since   3.0
 */
function pagination_item_active(&$item)
{
	// Find the icon to display
	$display = pagination_find_icon($item->text);

	$prefix = '';

	// This requires JavaScript !
	if ($item->base >= 0)
	{
		return '<span class="btn" title="' . $item->text . '"'
			. ' onclick="document.adminForm.' . $prefix . 'limitstart.value=' . $item->base
			. '; Joomla.submitform();return false;">' . $display . '</span>';
	}
	else
	{
		return '<span class="btn disabled" title="' . $item->text . '" onclick="document.adminForm.' . $prefix
			. 'limitstart.value=0; Joomla.submitform();return false;">' . $display . '</span>';
	}


	// <noscript> solution:
	//return "<li><a title=\"" . $item->text . "\" href=\"" . $item->link . "\" class=\"pagenav\">" . $display . "</a><li>";
}


/**
 * Renders an inactive item in the pagination block
 *
 * @param   JPaginationObject  $item  The current pagination object
 *
 * @return  string  HTML markup for inactive item
 *
 * @since   3.0
 */
function pagination_item_inactive(&$item)
{
	// Find the icon to display
	$display = pagination_find_icon($item->text);

	// Check if the item is the active page
	$active = (isset($item->active) && ($item->active)) ? ' active' : ' disabled';

	// Doesn't match any other condition, render a normal item
	return '<span class="btn' . $active . '">' . $display . '</span>';
}

/**
 * @param $text
 *
 * @return string
 */
function pagination_find_icon($text)
{
	switch ($text)
	{
		// Check for "Start" item
		case JText::_('JLIB_HTML_START') :
			return '<i class="icon-first"></i>';
			break;

		// Check for "Prev" item
		case JText::_('JPREV') :
			return '<i class="icon-previous"></i>';
			break;

		// Check for "Next" item
		case JText::_('JNEXT') :
			return '<i class="icon-next"></i>';
			break;

		// Check for "End" item
		case JText::_('JLIB_HTML_END') :
			return '<i class="icon-last"></i>';
			break;
	}

	return $text;
}
