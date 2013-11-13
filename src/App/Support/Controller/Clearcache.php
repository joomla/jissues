<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\Support\Controller;

use g11n\g11n;

use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for the icons view.
 *
 * @since  1.0
 */
class Clearcache extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$application = $this->container->get('app');

		try
		{
			g11n::cleanCache();

			$application->enqueueMessage(g11n3t('The cache has been cleared.'), 'success');
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');
		}

		$application->redirect('/');
	}
}
