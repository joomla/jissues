<?php
/**
 * Part of the Joomla Tracker DiffRenderer Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\DiffRenderer\Html;

use Adaptive\Diff\Renderer\Html\ArrayRenderer;

/**
 * Class Inline
 *
 * @since  1.0
 */
class Inline extends ArrayRenderer
{
	private $showLineNumbers = true;

	private $showHeader = true;

	/**
	 * Render a and return diff with changes between the two sequences displayed inline (under each other).
	 *
	 * @return string The generated inline diff.
	 *
	 * @since  1.0
	 */
	public function render()
	{
		$changes = parent::render();
		$html = '';

		if (empty($changes))
		{
			return $html;
		}

		$html .= '<table class="Differences DifferencesInline">';

		if ($this->showHeader)
		{
			$html .= '<thead>';
			$html .= '<tr>';

			if ($this->showLineNumbers)
			{
				$html .= '<th>Old</th>';
				$html .= '<th>New</th>';
			}

			$html .= '<th>Differences</th>';
			$html .= '</tr>';
			$html .= '</thead>';
		}

		foreach ($changes as $i => $blocks)
		{
			// If this is a separate block, we're condensing code so output ...,
			// indicating a significant portion of the code has been collapsed as it is the same
			if ($i > 0)
			{
				$html .= '<tbody class="Skipped">';
				$html .= '<th>&hellip;</th>';

				if ($this->showLineNumbers)
				{
					$html .= '<th>&hellip;</th>';
					$html .= '<td>&nbsp;</td>';
				}

				$html .= '</tbody>';
			}

			foreach ($blocks as $change)
			{
				$html .= '<tbody class="Change' . ucfirst($change['tag']) . '">';

				// Equal changes should be shown on both sides of the diff

				if ($change['tag'] == 'equal')
				{
					foreach ($change['base']['lines'] as $no => $line)
					{
						$html .= '<tr>';

						if ($this->showLineNumbers)
						{
							$html .= '<th>' . ($change['base']['offset'] + $no + 1) . '</th>';
							$html .= '<th>' . ($change['changed']['offset'] + $no + 1) . '</th>';
						}

						$html .= '<td class="Left">' . $line . '</td>';
						$html .= '</tr>';
					}
				}
				// Added lines only on the right side
				elseif ($change['tag'] == 'insert')
				{
					foreach ($change['changed']['lines'] as $no => $line)
					{
						$html .= '<tr>';

						if ($this->showLineNumbers)
						{
							$html .= '<th>&nbsp;</th>';
							$html .= '<th>' . ($change['changed']['offset'] + $no + 1) . '</th>';
						}

						$html .= '<td class="Right"><ins>' . $line . '</ins>&nbsp;</td>';
						$html .= '</tr>';
					}
				}
				// Show deleted lines only on the left side
				elseif ($change['tag'] == 'delete')
				{
					foreach ($change['base']['lines'] as $no => $line)
					{
						$html .= '<tr>';

						if ($this->showLineNumbers)
						{
							$html .= '<th>' . ($change['base']['offset'] + $no + 1) . '</th>';
							$html .= '<th>&nbsp;</th>';
						}

						$html .= '<td class="Left"><del>' . $line . '</del>&nbsp;</td>';
						$html .= '</tr>';
					}
				}
				// Show modified lines on both sides
				elseif ($change['tag'] == 'replace')
				{
					foreach ($change['base']['lines'] as $no => $line)
					{
						$html .= '<tr>';

						if ($this->showLineNumbers)
						{
							$html .= '<th>' . ($change['base']['offset'] + $no + 1) . '</th>';
							$html .= '<th>&nbsp;</th>';
						}

						$html .= '<td class="Left"><span>' . $line . '</span></td>';
						$html .= '</tr>';
					}

					foreach ($change['changed']['lines'] as $no => $line)
					{
						$html .= '<tr>';

						if ($this->showLineNumbers)
						{
							$html .= '<th>&nbsp;</th>';
							$html .= '<th>' . ($change['changed']['offset'] + $no + 1) . '</th>';
						}

						$html .= '<td class="Right"><span>' . $line . '</span></td>';
						$html .= '</tr>';
					}
				}

				$html .= '</tbody>';
			}
		}

		$html .= '</table>';

		return $html;
	}

	/**
	 * Set showLineNumbers.
	 *
	 * @param   boolean  $showLineNumbers  Show the line numbers.
	 *
	 * @return  $this
	 *
	 * @since  1.0
	 */
	public function setShowLineNumbers($showLineNumbers)
	{
		$this->showLineNumbers = (bool) $showLineNumbers;

		return $this;
	}

	/**
	 * Set showHeader.
	 *
	 * @param   boolean  $showHeader  Show the table header.
	 *
	 * @return  $this
	 *
	 * @since  1.0
	 */
	public function setShowHeader($showHeader)
	{
		$this->showHeader = (bool) $showHeader;

		return $this;
	}
}
