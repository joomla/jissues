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
	/**
	 * Display a link to an issue.
	 *
	 * @param   integer  $id      The issue id.
	 * @param   boolean  $closed  True if the issue is closed
	 * @param   string   $text    The text to display
	 *
	 * @return string
	 */
	public static function link($id, $closed = false, $text = '')
	{
		$text = ($text) ? : ' #' . $id;

		$link = JHtml::link(JRoute::_('index.php?option=com_tracker&view=issue&id=' . $id), $text);

		return ($closed) ? '<del>' . $link . '</del>' : $link;
	}

	/**
	 * Display a link to a commit on GitHub.
	 *
	 * @param   JTrackerProject  $project  The project.
	 * @param   string           $sha      The commit SHA.
	 *
	 * @return string
	 */
	public static function commit(JTrackerProject $project, $sha)
	{
		return JHtml::link(
			'https://github.com/' . $project->gh_user . '/' . $project->gh_project . '/commit/' . $sha,
			substr($sha, 0, 10)
		);
	}
}
