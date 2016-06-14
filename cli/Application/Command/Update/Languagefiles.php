<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

use ElKuKu\Crowdin\Languagefile;

use g11n\Language\Storage;
use g11n\Support\ExtensionHelper;

use Joomla\Filter\OutputFilter;

use JTracker\Helper\LanguageHelper;

/**
 * Class for updating resources on a translation service.
 *
 * @since  1.0
 */
class Languagefiles extends Update
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

		$this->description = g11n3t('Updates language files on a translation service.');
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
			->setupLanguageProvider()
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

		LanguageHelper::addDomainPaths();

		foreach (LanguageHelper::getScopes() as $domain => $extensions)
		{
			foreach ($extensions as $extension)
			{
				$name  = $extension . ' ' . $domain;

				$alias = OutputFilter::stringUrlUnicodeSlug($name);

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
						switch ($this->languageProvider)
						{
							case 'transifex':
								$this->transifex->resources->createResource(
									$this->getApplication()->get('transifex.project'), $name, $alias, 'PO', ['file' => $templatePath]
								);

								break;

							case 'crowdin':
								$this->crowdin->file->add(new Languagefile($templatePath, $alias . '_en.po'));

								break;
						}

						$this->out('<ok>Resource created successfully</ok>');
					}
					else
					{
						switch ($this->languageProvider)
						{
							case 'transifex':
								$this->transifex->resources->updateResourceContent(
									$this->getApplication()->get('transifex.project'), $alias, $templatePath, 'file'
								);

								break;

							case 'crowdin':
								$this->crowdin->file->update(new Languagefile($templatePath, $alias . '_en.po'));

								break;
						}

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
		if ('crowdin' != $this->languageProvider)
		{
			// Currently this is only enabled for Crowdin.
			return $this;
		}

		if (!$this->getApplication()->input->get('translations'))
		{
			return $this;
		}

		defined('JDEBUG') || define('JDEBUG', 0);

		LanguageHelper::addDomainPaths();

		foreach (LanguageHelper::getScopes() as $domain => $extensions)
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
						$this->crowdin->translation->upload(new Languagefile($path, $fileName), LanguageHelper::getCrowdinLanguageTag($language), true, true);

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
