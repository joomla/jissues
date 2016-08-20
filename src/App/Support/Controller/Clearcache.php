<?php
/**
 * Part of the Joomla Tracker's Support Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Support\Controller;

use ElKuKu\G11n\Support\ExtensionHelper;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use JTracker\Controller\AbstractTrackerController;

/**
 * Controller class for clearing the application's cache.
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

			$application->enqueueMessage(g11n3t('The translation cache has been cleared.'), 'success');
		}
		catch (\Exception $e)
		{
			$application->enqueueMessage($e->getMessage(), 'error');
		}

		// Skip Twig cache if disabled
		if (!$application->get('renderer.cache', false))
		{
			$application->redirect('/');
		}

		$cacheDir     = JPATH_ROOT . '/cache';
		$twigCacheDir = $application->get('renderer.cache');

		$filesystem = new Filesystem(new Local($cacheDir));

		if ($filesystem->has($twigCacheDir))
		{
			if (!$filesystem->deleteDir($twigCacheDir))
			{
				$application->enqueueMessage(g11n3t('There was an error clearing the Twig cache.'), 'error');
			}
			else
			{
				$application->enqueueMessage(g11n3t('The Twig cache has been cleared.'), 'success');
			}
		}

		$application->redirect('/');
	}
}
