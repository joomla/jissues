<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use ElKuKu\Crowdin\Languagefile;

use g11n\Language\Storage;
use g11n\Support\ExtensionHelper;

use Joomla\Filter\OutputFilter;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class for updating resources on Crowdin
 *
 * @since  1.0
 */
class Crowdin extends Update
{
	/**
	 * Array containing application languages.
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

		$this->description = g11n3t('Updates language files on Crowdin.');
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
		$this->getApplication()->outputTitle('Update Translations');

		$this->languages = $this->getApplication()->get('languages');

		$this->logOut('Start pushing translations.')
			->setupCrowdin()
			->uploadTemplates()
			->uploadTranslations()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Push translation templates.
	 *
	 * @return  $this
	 *
	 * @throws \DomainException
	 * @since   1.0
	 */
	private function uploadTemplates()
	{
		$create = $this->getApplication()->input->get('create');

		defined('JDEBUG') || define('JDEBUG', 0);

		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('CoreJS', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');
		ExtensionHelper::addDomainPath('CLI', JPATH_ROOT);

		$scopes = [
			'Core' => ['JTracker'],
			'CoreJS' => ['JTracker.js'],
			'Template' => ['JTracker'],
			'CLI' => ['cli'],
			'App' => (new Filesystem(new Local(JPATH_ROOT . '/src/App')))->listPaths()
		];

		foreach ($scopes as $domain => $extensions)
		{
			foreach ($extensions as $extension)
			{
				$name  = $extension . ' ' . $domain;

				$alias = OutputFilter::stringUrlUnicodeSlug($name);
				$alias .= '_en.po';

				$this->out('Processing: ' . $name . ' - ' . $alias);

				$templatePath = Storage::getTemplatePath($extension, $domain);

				if (false == file_exists($templatePath))
				{
					throw new \DomainException(sprintf('Language template for %s not found.', $name));
				}

				$this->out($templatePath);

				try
				{
					if ($create)
					{
						$this->crowdin->file->add(new Languagefile($templatePath, $alias));

						$this->out('<ok>Resource created successfully</ok>');
					}
					else
					{
						$this->crowdin->file->update(new Languagefile($templatePath, $alias));

						$this->out('<ok>Resource updated successfully</ok>');
					}
				}
				catch (\Exception $e)
				{
					$this->out('<error>' . $e->getMessage() . '</error>');
				}

				$this->out();
			}
		}

		return $this;
	}

	/**
	 * Push translations.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	private function uploadTranslations()
	{
		if (!$this->getApplication()->input->get('translations'))
		{
			return $this;
		}

		// @temp - List with known "exceptions" - @todo move
		$langMap = [
			'es-ES' => 'es-ES',
			'nb-NO' => 'no',
			'pt-BR' => 'pt-BR',
			'pt-PT' => 'pt-PT',
			'zh-CN' => 'zh-CN'
		];

		defined('JDEBUG') || define('JDEBUG', 0);

		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('CoreJS', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');
		ExtensionHelper::addDomainPath('CLI', JPATH_ROOT);

		$scopes = [
			'Core' => ['JTracker'],
			'CoreJS' => ['JTracker.js'],
			'Template' => ['JTracker'],
			'CLI' => ['cli'],
			'App' => (new Filesystem(new Local(JPATH_ROOT . '/src/App')))->listPaths()
		];

		foreach ($scopes as $domain => $extensions)
		{
			$scopePath = ExtensionHelper::getDomainPath($domain);

			foreach ($extensions as $extension)
			{
				$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

				$this->out(sprintf('Processing: %s %s... ', $domain, $extension), false);

				foreach ($this->languages as $language)
				{
					if ('en-GB' == $language)
					{
						continue;
					}

					$this->out($language . '... ', false);

					$fileName = strtolower(str_replace('.', '-', $extension)) . '-' . strtolower($domain) . '_en.po';

					// Get the "Sink"
					$path = $scopePath . '/' . $extensionPath . '/' . $language . '/' . $language . '.' . $extension . '.po';

					if (false == is_dir(dirname($path)))
					{
						$this->out('<info>NOT FOUND</info>... ', false);

						continue;
					}

					// Call out to Crowdin
					try
					{
						$langTag = array_key_exists($language, $langMap) ? $langMap[$language] : substr($language, 0, 2);

						$this->crowdin->translation->upload(new Languagefile($path, $fileName), $langTag, true, true);

						$this->out('ok... ', false);
					}
					catch (\Exception $e)
					{
						$this->out('<error>' . $e->getMessage() . '</error>');
					}
				}

				$this->out();
			}
		}

		return $this;
	}
}
