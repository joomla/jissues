<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use g11n\Support\ExtensionHelper;
use JTracker\Crowdin\Api\ExportFile;

/**
 * Class for retrieving translations from Transifex
 *
 * @since  1.0
 */
class Crowdin extends Get
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

		$this->description = g11n3t('Retrieve language files from Crowdin.');
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
			->setupCrowdin()
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
		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('CoreJS', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');
		ExtensionHelper::addDomainPath('CLI', JPATH_ROOT);

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

			$this->receiveFiles($fileInfo->getFileName(), 'App');
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
		// @temp - @todo move
		$langMap = [
			'es-ES' => 'es-ES',
			'nb-NO' => 'no',
			'pt-BR' => 'pt-BR',
			'pt-PT' => 'pt-PT',
			'zh-CN' => 'zh-CN'
		];

		$this->out(sprintf('Processing: %s %s... ', $domain, $extension), false);

		$scopePath     = ExtensionHelper::getDomainPath($domain);
		$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

		/** @var $api ExportFile */
		$api = $this->crowdin->api('export-file');

		// Fetch the file for each language and place it in the file tree
		foreach ($this->languages as $language)
		{
			if ('en-GB' == $language)
			{
				continue;
			}

			$this->out($language . '... ', false);

			$fileName = strtolower(str_replace('.', '-', $extension)) . '-' . strtolower($domain) . '_en.po';

			// Create the "Sink"
			$path = $scopePath . '/' . $extensionPath . '/' . $language . '/' . $language . '.' . $extension . 'XX.po';

			if (false == is_dir(dirname($path)))
			{
				if (false == mkdir(dirname($path)))
				{
					throw new \Exception('Could not create the directory at: ' . str_replace(JPATH_ROOT, '', dirname($path)));
				}
			}

			// Call out to Crowdin
			$api->setFile($fileName)
				->setLanguage(array_key_exists($language, $langMap) ? $langMap[$language] : substr($language, 0, 2))
				->setSink($path)
				->execute();
		}

		$this->out('ok');
	}
}
