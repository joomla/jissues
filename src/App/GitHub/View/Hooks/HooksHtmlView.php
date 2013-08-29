<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\View\Hooks;

use JTracker\View\AbstractTrackerHtmlView;
use JTracker\Container;

/**
 * The hooks view
 *
 * @since  1.0
 */
class HooksHtmlView extends AbstractTrackerHtmlView
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
		$app = Container::retrieve('app');
		$this->renderer->set('project', $app->getProject());

		return parent::render();
	}
}
