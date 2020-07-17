<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\View\Icons;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * The icons view
 *
 * @since  1.0
 */
class IconsHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function render()
	{
		$file = file_get_contents(JPATH_THEMES . '/media/css/template.css');

		preg_match_all('/.(icon-[a-z0-9\-]+:before)/', $file, $matches);

		$icons = $matches[1];

		array_walk(
			$icons,
			static function (string &$selector): void {
				$selector = str_replace(':before', '', $selector);
			}
		);

		$icons = array_unique($icons);
		sort($icons);

		$this->addData('icons', $icons);

		$file = file_get_contents(JPATH_THEMES . '/media/css/vendor/octicons.css');

		preg_match_all('/.(octicon-[a-z0-9\-]+:before)/', $file, $matches);

		$icons = $matches[1];

		array_walk(
			$icons,
			static function (string &$selector): void {
				$selector = str_replace(':before', '', $selector);
			}
		);

		$icons = array_unique($icons);
		sort($icons);

		$this->addData('octicons', $icons);

		return parent::render();
	}
}
