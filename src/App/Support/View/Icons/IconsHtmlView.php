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
		$lines = file(JPATH_THEMES . '/jtracker/core/css/template.css');

		$icons = array();

		foreach ($lines as $line)
		{
			if (preg_match('/.(icon-[a-z0-9\-]+)/', $line, $matches))
			{
				if ('icon-bar' == $matches[1])
				{
					continue;
				}

				$icons[] = $matches[1];
			}
		}

		$this->renderer->set('icons', array_unique($icons));

		// Read octicons
		$lines = file(JPATH_THEMES . '/fonts/octicons/octicons.css');

		$icons = array();

		foreach ($lines as $line)
		{
			if (preg_match('/.(octicon-[a-z0-9\-]+)/', $line, $matches))
			{
				$icons[] = $matches[1];
			}
		}

		$this->renderer->set('octicons', array_unique($icons));

		return parent::render();
	}
}
