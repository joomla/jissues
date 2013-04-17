<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Utility class for tracker events.
 *
 * @package     JTracker
 * @subpackage  HTML
 * @since       1.0
 */
abstract class JHtmlTrackerevent
{
	/**
	 * Returns an array of statuses for a filter list.
	 *
	 * @param   JTrackerProject  $project  The project
	 * @param   string           $json     The JSON strong containing the events.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function displayTable(JTrackerProject $project, $json)
	{
		$changes = json_decode($json);

		if (!$changes)
		{
			return 'Invalid changes string';
		}

		$html = array();

		$html[] = '<table class="table table-bordered table-condensed table-striped">';

		$html[] = '<tr>
				<th>Name</th>
				<th>Old</th>
				<th>New</th>
			</tr>';

		foreach ($changes as $change)
		{
			$html[] = '<tr>';
			$html[] = '<td>' . $change->name . '</td>';
			$html[] = '<td>';

			switch ($change->name)
			{
				case 'status' :
					$html[] = JHtmlStatus::item($change->old);
					break;

				default :
					$html[] = $change->old;
					break;
			}

			$html[] = '</td>';
			$html[] = '<td>';

			switch ($change->name)
			{
				case 'status' :
					$html[] = JHtmlStatus::item($change->new);
					break;

				case 'closed_sha' :
					$html[] = JHtmlIssues::commit($project, $change->new);
					break;

				default :
					$html[] = $change->new;
					break;
			}

			$html[] = '</td>';
			$html[] = '</tr>';
		}

		$html[] = '</table>';

		return implode("\n", $html);
	}
}
