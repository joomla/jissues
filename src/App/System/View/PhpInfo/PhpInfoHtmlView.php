<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\System\View\PhpInfo;

use JTracker\View\AbstractTrackerHtmlView;
use Joomla\Utilities\ArrayHelper;
use Twig_SimpleFilter;

/**
 * System phpinfo() output view.
 *
 * @since  1.0
 */
class PhpInfoHtmlView extends AbstractTrackerHtmlView
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
		ob_start();
		date_default_timezone_set('UTC');
		phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES);
		$phpinfo = ob_get_clean();

		preg_match_all('#<body[^>]*>(.*)</body>#siU', $phpinfo, $output);
		$output = preg_replace('#<table[^>]*>#', '<table class="table table-striped adminlist">', $output[1][0]);
		$output = preg_replace('#(\w),(\w)#', '\1, \2', $output);
		$output = preg_replace('#<hr />#', '', $output);
		$output = str_replace('<div class="center">', '', $output);
		$output = preg_replace('#<tr class="h">(.*)<\/tr>#', '<thead><tr class="h">$1</tr></thead><tbody>', $output);
		$output = str_replace('</table>', '</tbody></table>', $output);
		$output = str_replace('</div>', '', $output);

		$this->renderer->set('phpinfo', $output);

		return parent::render();
	}
}
