<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012-2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View;

use App\Projects\ProjectAwareTrait;

use Joomla\View\BaseHtmlView;

/**
 * Abstract HTML view class for the Tracker application
 *
 * @since  1.0
 */
abstract class AbstractTrackerHtmlView extends BaseHtmlView
{
	use ProjectAwareTrait;

	/**
	 * The view layout.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $layout = 'index';
}
