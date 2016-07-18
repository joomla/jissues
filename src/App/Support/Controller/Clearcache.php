<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Controller;

use ElKuKu\G11n\Support\ExtensionHelper;

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
		/* @type \JTracker\Application $application */
		$application = $this->getContainer()->get('app');

		$application->getUser()->authorize('admin');

		try
		{
			ExtensionHelper::cleanCache();

			$application->enqueueMessage(g11n3t('The cache has been cleared.'), 'success');
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');
		}

		$application->redirect('/');
	}
}
