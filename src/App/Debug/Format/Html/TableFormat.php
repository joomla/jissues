<?php
/**
 * Part of the Joomla Tracker's Debug Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Format\Html;

/**
 * Class TableFormat.
 *
 * @since  1.0
 */
class TableFormat
{
	/**
	 * Convert an array to a HTML table.
	 *
	 * @param   array  $array  The array to generate the table from.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function fromArray($array)
	{
		if (! $array)
		{
			return '';
		}

		$html = '<table class="table table-striped dbgQueryTable"><tr>';

		foreach (array_keys($array[0]) as $k)
		{
			$html .= '<th>' . htmlspecialchars($k) . '</th>';
		}

		$html .= '</tr>';

		foreach ($array as $tr)
		{
			$html .= '<tr>';

			foreach ($tr as $td)
			{
				$html .= '<td>' . ($td === null ? 'NULL' : htmlspecialchars($td)) . '</td>';
			}

			$html .= '</tr>';
		}

		$html .= '</table>';

		return $html;
	}

	/**
	 * Convert a stack trace to a HTML table.
	 *
	 * @param   array  $trace  The stack trace.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function fromTrace(array $trace)
	{
		$linkFormat = new LinkFormat;

		$html = [];

		$html[] = '<table class="table table-hover table-condensed">';

		foreach ($trace as $entry)
		{
			$html[] = '<tr>';
			$html[] = '<td>';

			if (isset($entry['file']))
			{
				$html[] = $linkFormat->formatLink($entry['file'], $entry['line']);
			}

			$html[] = '</td>';
			$html[] = '<td>';

			if (isset($entry['class']))
			{
				$html[] = $entry['class'] . $entry['type'] . $entry['function'] . '()';
			}
			elseif (isset($entry['function']))
			{
				$html[] = $entry['function'] . '()';
			}

			$html[] = '</td>';
			$html[] = '</tr>';
		}

		$html[] = '</table>';

		return implode("\n", $html);
	}
}
