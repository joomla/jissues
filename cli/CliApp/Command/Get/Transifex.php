<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Get;

use g11n\Support\ExtensionHelper;

/**
 * Class for retrieving translations from Transifex
 *
 * @since  1.0
 */
class Transifex extends Get
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Retrieve language files from Transifex.';

	/**
	 * Array containing application languages to retrieve translations for
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $languages = array();

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Get Translations');

		$this->languages = $this->getApplication()->get('languages');

		// Remove English from the language array
		unset($this->languages[0]);

		$this->logOut('Start fetching translations.')
			->setupTransifex()
			->fetchTranslations()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Fetch translations.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	private function fetchTranslations()
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

			// Skip apps with empty language templates
			if (in_array($extension, array('GitHub', 'System')))
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

		// Fetch the file for each language and place it in the file tree
		foreach ($this->languages as $language)
		{
			// Call out to Transifex
			$translation = $this->transifex->translations->getTranslation(
				$this->getApplication()->get('transifex.project'),
				strtolower($extension) . '-' . strtolower($domain),
				str_replace('-', '_', $language)
			);

			// Write the file
			$path = $scopePath . '/' . $extensionPath . '/' . $language . '/' . $language . '.' . $extension . '.po';

			if (false == is_dir(dirname($path)))
			{
				if (false == mkdir(dirname($path)))
				{
					throw new \Exception('Could not create the directory at: ' . str_replace(JPATH_ROOT, '', dirname($path)));
				}
			}

			if (!file_put_contents($path, $translation->content))
			{
				throw new \Exception('Could not store language file at: ' . str_replace(JPATH_ROOT, '', $path));
			}
		}
	}
}
