<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\System\View\Config;

use JTracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;
use Twig_SimpleFilter;

/**
 * System configuration view.
 *
 * @since  1.0
 */
class ConfigHtmlView extends AbstractTrackerHtmlView
{
	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function render()
	{
		$config = json_decode(file_get_contents(JPATH_CONFIGURATION . '/config.json'));

		// @todo Twig can not foreach() over stdclasses...
		$cfx = ArrayHelper::fromObject($config);

		$this->renderer->set('config', $cfx);

		// Format a string.
		$this->renderer->addFilter(
			new Twig_SimpleFilter(
				'tformat', function ($string)
				{
					$string = str_replace(array('_', '-'), ' ', $string);
					$string = ucfirst($string);

					return $string;
				}
			)
		);

		return parent::render();
	}
}
