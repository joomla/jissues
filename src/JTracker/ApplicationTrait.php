<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker;

/**
 * Trait defining common methods between application classes
 */
trait ApplicationTrait
{
	/**
	 * Loads the application's apps
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function bootApps()
	{
		// Find all components and if they have a AppInterface implementation load their services
		/** @var \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$className = 'App\\' . $fileInfo->getFilename() . '\\' . $fileInfo->getFilename() . 'App';

			if (class_exists($className))
			{
				/** @var AppInterface $object */
				$object = new $className;

				// Register the app services
				$object->loadServices($this->getContainer());
			}
		}
	}
}
