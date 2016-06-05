<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

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
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Update language files on Crowdin.';

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
			->setupCrowdin()
			->uploadTemplates()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Push translations.
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
						$api = $this->crowdin->api('add-file');
						$api->addTranslation($templatePath, $alias);

						$result = $api->execute();

						$this->out('<ok>Resource created successfully</ok>');
					}
					else
					{
						$api = $this->crowdin->api('update-file');
						$api->addTranslation($templatePath, $alias);

						$result = $api->execute();

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
}
