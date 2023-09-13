<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Model;

use Joomla\Model\StatefulModelInterface;
use Joomla\Model\StatefulModelTrait;

/**
 * Icons model class.
 *
 * @since  1.0
 */
class IconsModel implements StatefulModelInterface
{
	use StatefulModelTrait;

	/**
	 * Get the list of icons in the Joomla template.
	 *
	 * @return  string[]
	 *
	 * @since   1.0
	 */
	public function getJoomlaIcons(): array
	{
		return $this->parseCssFileForIcons(JPATH_THEMES . '/media/css/template.css', '/.(icon-[a-z0-9\-]+:before)/');
	}

	/**
	 * Get the list of icons from the Octicons set.
	 *
	 * @return  string[]
	 *
	 * @since   1.0
	 */
	public function getOcticons(): array
	{
		return $this->parseCssFileForIcons(JPATH_THEMES . '/media/css/vendor/octicons.css', '/.(octicon-[a-z0-9\-]+:before)/');
	}

	/**
	 * Parses a CSS file and extracts all icon classes matching a pattern.
	 *
	 * @param   string  $file     The CSS file to parse
	 * @param   string  $pattern  The regex to use for matching icons
	 *
	 * @return  string[]
	 *
	 * @since   1.0
	 */
	public function parseCssFileForIcons(string $file, string $pattern): array
	{
		$file = file_get_contents($file);

		preg_match_all($pattern, $file, $matches);

		$icons = $matches[1];

		array_walk(
			$icons,
			static function (string &$selector): void
			{
				$selector = str_replace(':before', '', $selector);
			}
		);

		$icons = array_unique($icons);
		sort($icons);

		return $icons;
	}
}
