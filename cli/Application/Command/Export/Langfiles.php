<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Export;

use g11n\Language\Storage as g11nStorage;
use g11n\Support\ExtensionHelper as g11nExtensionHelper;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class for retrieving avatars from GitHub for selected projects.
 *
 * @since  1.0
 */
class Langfiles extends Export
{
	/**
	 * List of supported languages.
	 *
	 * @var array
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

		$this->description = 'Backup language files to a given folder.';
	}

	/**
	 * Set up the environment to run the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function setup()
	{
		parent::setup();

		$this->languages = $this->getApplication()->get('languages');

		return $this;
	}

	/**
	 * Execute the command.
	 *
	 * @throws \RuntimeException
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Export language files');

		$this->setup()
			->logOut('Start exporting language files.')
			->exportFiles()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Create list of files to export.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	private function exportFiles()
	{
		g11nExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		g11nExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		g11nExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');

		$scopes = array(
			'Core' => array(
				'JTracker'
			),
			'Template' => array(
				'JTracker'
			),
			'App' => (new Filesystem(new Local(JPATH_ROOT . '/src/App')))->listPaths()
		);

		$templates = $this->getApplication()->input->getCmd('templates');

		foreach ($scopes as $domain => $extensions)
		{
			foreach ($extensions as $extension)
			{
				$this->processDomain($extension, $domain, $templates);
			}
		}

		return $this;
	}

	/**
	 * Process language files for a domain.
	 *
	 * @param   string   $extension  Extension name.
	 * @param   string   $domain     Extension domain.
	 * @param   boolean  $templates  If templates should be exported.
	 *
	 * @throws \DomainException
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processDomain($extension, $domain, $templates)
	{
		$domainBase = trim(str_replace(JPATH_ROOT, '', g11nExtensionHelper::getDomainPath($domain)), '/');
		$g11nPath = g11nExtensionHelper::$langDirName;

		$filesystem = new Filesystem(new Local($this->exportDir . '/' . $domainBase . '/' . $extension . '/' . $g11nPath));

		$this->out(sprintf('Processing %s %s:... ', $domain, $extension), false);

		// Process language templates
		if ($templates)
		{
			$this->out('templates... ', false);

			$path = g11nStorage::getTemplatePath($extension, $domain);

			$contents = (new Filesystem(new Local(dirname($path))))
				->read(basename($path));

			if (false == $filesystem->put('templates/' . basename($path), $contents))
			{
				throw new \DomainException('Can not write the file at: ' . $path);
			}
		}

		// Process language files
		foreach ($this->languages as $lang)
		{
			if ('en-GB' == $lang)
			{
				continue;
			}

			$this->out($lang . '... ', false);

			$path = g11nExtensionHelper::findLanguageFile($lang, $extension, $domain);

			if (!$path)
			{
				$this->out('<error> ' . $lang . ' NOT FOUND </error>... ', false);

				continue;
			}

			$contents = (new Filesystem(new Local(dirname($path))))
				->read(basename($path));

			if (false == $filesystem->put($lang . '/' . basename($path), $contents))
			{
				throw new \DomainException('Can not write the file: ' . basename($path));
			}
		}

		$this->out('ok');

		return $this;
	}
}
