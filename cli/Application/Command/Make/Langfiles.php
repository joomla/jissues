<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

use ElKuKu\G11n\Language\Storage;
use ElKuKu\G11n\Support\ExtensionHelper;

use JTracker\Helper\LanguageHelper;

use PHP_CodeSniffer_File;

/**
 * Class for generating language template files.
 *
 * @since  1.0
 */
class Langfiles extends Make
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Create and update language files.');

		$this->addOption(
			new TrackerCommandOption(
				'extension', '',
				g11n3t('Process only this extension')
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle(g11n3t('Make Language files'));

		LanguageHelper::addDomainPaths();

		$languages = LanguageHelper::getLanguageCodes();

		$reqExtension = $this->getOption('extension');

		// Process the CLI application

		if (!$reqExtension || $reqExtension == 'cli')
		{
			$extension = 'cli';
			$domain    = 'CLI';

			$this->out('Processing: ' . $domain . ' ' . $extension);

			foreach ($languages as $lang)
			{
				if ('en-GB' == $lang)
				{
					continue;
				}

				$this->processDomain($extension, 'CLI', $lang);
			}
		}

		// Process JTracker core

		if (!$reqExtension || $reqExtension == 'JTracker')
		{
			foreach ($languages as $lang)
			{
				if ('en-GB' == $lang)
				{
					continue;
				}

				$this
					->processDomain('JTracker', 'Core', $lang)
					->processDomain('JTracker.js', 'Core', $lang)
					->processDomain('JTracker', 'Template', $lang);
			}
		}

		// Process App templates

		/** @var \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$extension = $fileInfo->getFilename();

			if ($reqExtension && $reqExtension != $extension)
			{
				continue;
			}

			$this->out('Processing App: ' . $extension);

			foreach ($languages as $lang)
			{
				if ('en-GB' == $lang)
				{
					continue;
				}

				$this->processDomain($extension, 'App', $lang);
			}
		}

		$this->out()
			->out(g11n3t('Finished.'));
	}

	/**
	 * Process language files for a domain.
	 *
	 * @param   string  $extension  Extension name.
	 * @param   string  $domain     Extension domain.
	 * @param   string  $lang       Language tag e.g. en-GB or de-DE.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function processDomain($extension, $domain, $lang)
	{
		$this->out(sprintf(g11n3t('Processing: %1$s %2$s %3$s'), $domain, $extension, $lang));

		$languageFile = ExtensionHelper::findLanguageFile($lang, $extension, $domain);
		$templateFile = Storage::getTemplatePath($extension, $domain);

		// Check if the language file has UNIX style line endings.
		if ("\n" != PHP_CodeSniffer_File::detectLineEndings($templateFile))
		{
			$this->out($templateFile)
				->out('<error>' . g11n3t('The file does not have UNIX style line endings!') . '</error>')
				->out();

			return $this;
		}

		if (false === $languageFile)
		{
			$this->out(g11n3t('Creating language file...'));

			$scopePath     = ExtensionHelper::getDomainPath($domain);
			$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

			$path = $scopePath . '/' . $extensionPath . '/' . $lang;

			if (!is_dir($path))
			{
				if (!mkdir($path, 0755, true))
				{
					throw new \Exception('Can not create the language folder');
				}
			}

			$fileName = $lang . '.' . $extension . '.po';

			$options = [];

			$options[] = 'input=' . $templateFile;
			$options[] = 'output=' . $path . '/' . $fileName;
			$options[] = 'no-wrap';
			$options[] = 'locale=' . $lang;

			$cmd = 'msginit --' . implode(' --', $options) . ' 2>&1';

			$this->debugOut($cmd);

			ob_start();

			system($cmd);

			$msg = ob_get_clean();

			if (!file_exists($templateFile))
			{
				throw new \Exception('Can not create the language file');
			}

			$this->out(g11n3t('The language file has been created'))
				->out($msg);
		}
		else
		{
			$this->out(g11n3t('Updating language file...'));

			$options = [];

			$options[] = 'update';
			$options[] = 'backup=off';
			$options[] = 'no-fuzzy-matching';
			$options[] = 'verbose';
			$options[] = 'no-wrap';

			$paths = [];
			$paths[] = $languageFile;
			$paths[] = $templateFile;

			$cmd = 'msgmerge --'
				. implode(' --', $options)
				. ' "' . implode('" "', $paths) . '"'
				. ' 2>&1';

			$this->debugOut($cmd);

			ob_start();

			system($cmd);

			$msg = ob_get_clean();

			if ($msg)
			{
				$this->out($msg);
			}
		}

		return $this;
	}
}
