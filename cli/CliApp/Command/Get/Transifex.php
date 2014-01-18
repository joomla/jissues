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
 * Class for retrieving avatars from GitHub for selected projects
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
	 * @throws  \UnexpectedValueException
	 */
	private function fetchTranslations()
	{
		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/cache/twig');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/cache/twig');

		defined('JDEBUG') || define('JDEBUG', 0);

		// Process core files
		$extension = 'JTracker';
		$domain    = 'Core';

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

			if (!file_put_contents($path, $translation->content))
			{
				throw new \Exception('Could not store language file at: ' . str_replace(JPATH_ROOT, '', $path));
			}
		}

		return $this;
	}
}
