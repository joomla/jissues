<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Support\View\Icons;

use JTracker\View\AbstractTrackerHtmlView;

/**
 * The issues item view
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
		$lines = file(JPATH_THEMES . '/css/template.css');

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

		return parent::render();
	}
}
