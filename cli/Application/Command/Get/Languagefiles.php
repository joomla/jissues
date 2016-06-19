<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use g11n\Support\ExtensionHelper;

use JTracker\Helper\LanguageHelper;

/**
 * Class for retrieving translations files.
 *
 * @since  1.0
 */
class Languagefiles extends Get
{
	/**
	 * Array containing application languages to retrieve translations for
	 *
	 * @var    array
	 * @since  1.0
	 */
	private $languages = array();

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Retrieve language files.');
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle(g11n3t('Get Translations'));

		$this->languages = $this->getApplication()->get('languages');

		// Remove English from the language array
		unset($this->languages[0]);

		$this->logOut(g11n3t('Start fetching translations.'))
			->setupLanguageProvider()
			->fetchTranslations()
			->out()
			->logOut(g11n3t('Finished.'));
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
		LanguageHelper::addDomainPaths();

		defined('JDEBUG') || define('JDEBUG', 0);

		// Process CLI files
		$this->receiveFiles('cli', 'CLI');

		// Process core files
		$this->receiveFiles('JTracker', 'Core');

		// Process core JS files
		$this->receiveFiles('JTracker.js', 'CoreJS');

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

			$this->receiveFiles($fileInfo->getFilename(), 'App');
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
		$this->out(sprintf('Processing: %s %s... ', $domain, $extension), false);

		$scopePath     = ExtensionHelper::getDomainPath($domain);
		$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

		// Fetch the file for each language and place it in the file tree
		foreach ($this->languages as $language)
		{
			if ('en-GB' == $language)
			{
				continue;
			}

			$this->out($language . '... ', false);

			// Write the file
			$path = $scopePath . '/' . $extensionPath . '/' . $language . '/' . $language . '.' . $extension . '.po';

			if (false == is_dir(dirname($path)))
			{
				if (false == mkdir(dirname($path)))
				{
					throw new \Exception('Could not create the directory at: ' . str_replace(JPATH_ROOT, '', dirname($path)));
				}
			}

			switch ($this->languageProvider)
			{
				case 'transifex':
					$translation = $this->transifex->translations->getTranslation(
						$this->getApplication()->get('transifex.project'),
						strtolower(str_replace('.', '-', $extension)) . '-' . strtolower($domain),
						str_replace('-', '_', $language)
					);

					if (!file_put_contents($path, $translation->content))
					{
						throw new \Exception('Could not store language file at: ' . str_replace(JPATH_ROOT, '', $path));
					}
					break;

				case 'crowdin':
					$fileName = strtolower(str_replace('.', '-', $extension)) . '-' . strtolower($domain) . '_en.po';
					$this->crowdin->file->export($fileName, LanguageHelper::getCrowdinLanguageTag($language), $path);
					break;
			}
		}

		$this->out('ok');
	}
}
