<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use CliApp\Application\TrackerApplication;
use g11n\Language\Storage;
use g11n\Support\ExtensionHelper;

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
	 * @param   TrackerApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;
		$this->description = 'Create and update language files.';
	}

	/**
	 * Execute the command.
	 *
	 * @throws \Exception
	 * @since   1.0
	 * @return  void
	 */
	public function execute()
	{
		$this->application->outputTitle('Make Language files');

		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');

		$languages = $this->application->get('languages');

		foreach ($languages as $lang)
		{
			if ('en-GB' == $lang)
			{
				continue;
			}

			$this->out('Processing: JTracker Core ' . $lang);
			$this->processDomain('JTracker', 'Core', $lang);

			$this->out('Processing: JTracker Template ' . $lang);
			$this->processDomain('JTracker', 'Template', $lang);
		}

		// Process App templates

		/* @type \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$extension = $fileInfo->getFileName();

			$this->out('Processing: ' . $extension);

			foreach ($languages as $lang)
			{
				if ('en-GB' == $lang)
				{
					continue;
				}

				$this->out('Processing: App ' . $extension . ' ' . $lang);
				$this->processDomain($extension, 'App', $lang);
			}
		}

		$this->out()
			->out('Finished =;)');
	}

	/**
	 * Process language files for a domain.
	 *
	 * @param   string  $extension  Extension name.
	 * @param   string  $domain     Extension domain.
	 * @param   string  $lang       Language tag e.g. en-GB or de-DE.
	 *
	 * @throws \Exception
	 * @return $this
	 */
	protected function processDomain($extension, $domain, $lang)
	{
		$languageFile = ExtensionHelper::findLanguageFile($lang, $extension, $domain);
		$templateFile = Storage::getTemplatePath($extension, $domain);

		if (false == $languageFile)
		{
			$this->out('Creating language file...');

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

			$options = array();

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
				throw new \Exception('Can not copy create the language file');
			}

			$this->out('The language file has been created')
				->out($msg);
		}
		else
		{
			$this->out('Updating language file...');

			$options = array();

			$options[] = 'update';
			$options[] = 'backup=numbered';
			$options[] = 'no-fuzzy-matching';
			$options[] = 'verbose';
			$options[] = 'no-wrap';

			$paths = array();
			$paths[] = $languageFile;
			$paths[] = $templateFile;

			$cmd = 'msgmerge --'
				. implode(' --', $options)
				. ' "' . implode('" "', $paths) . '"'
				.  ' 2>&1';

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
