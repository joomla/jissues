<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Update;

use g11n\Support\ExtensionHelper;

/**
 * Class for updating resources on Transifex
 *
 * @since  1.0
 */
class Transifex extends Update
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Update language files on Transifex.';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Update Translations');

		$this->logOut('Start pushing translations.')
			->setupTransifex()
			->pushTranslations()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Push translations.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	private function pushTranslations()
	{
		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');

		defined('JDEBUG') || define('JDEBUG', 0);

		// Process core files
		$this->receiveFiles('JTracker', 'Core');

		// Process template files
		$this->receiveFiles('JTracker', 'Template');

		// Process app files
		/* @type \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$extension = $fileInfo->getFileName();

			// Skip apps with empty language templates, also the Debug app as the Transifex object won't send it
			if (in_array($extension, array('Debug', 'GitHub', 'System')))
			{
				continue;
			}

			$this->receiveFiles($extension, 'App');
		}

		return $this;
	}

	/**
	 * Receives language files from Transifex
	 *
	 * @param   string  $extension  The extension to process
	 * @param   string  $domain     The domain of the extension
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	private function receiveFiles($extension, $domain)
	{
		$this->out('Processing: ' . $domain . ' ' . $extension);

		$scopePath     = ExtensionHelper::getDomainPath($domain);
		$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

		// Call out to Transifex
		$translation = $this->transifex->resources->updateResourceContent(
			$this->getApplication()->get('transifex.project'),
			strtolower($extension) . '-' . strtolower($domain),
			$scopePath . '/' . $extensionPath . '/templates/' . $extension . '.pot',
			'file'
		);
	}
}
