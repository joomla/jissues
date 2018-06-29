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
use ElKuKu\G11nUtil\G11nUtil;
use ElKuKu\G11nUtil\Type\LanguageTemplateType;
use JTracker\View\Renderer\TrackerExtension;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class for generating language template files.
 *
 * @since  1.0
 */
class Langtemplates extends Make
{
	/**
	 * The software product.
	 *
	 * @var    \stdClass
	 * @since  1.0
	 */
	private $product = null;

	/**
	 * @var G11nUtil
	 */
	private $g11nUtil;

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Create language file templates.');

		$this->addOption(
			new TrackerCommandOption(
				'extension', '',
				g11n3t('Process only this extension')
			)
		);

		$this->product = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
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
		$this->getApplication()->outputTitle(g11n3t('Make Language templates'));

		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('CoreJS', JPATH_ROOT . '/www/media/js');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/cache/twig');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/cache/twig');
		ExtensionHelper::addDomainPath('CLI', JPATH_ROOT);

		defined('JDEBUG') || define('JDEBUG', 0);

		$reqExtension = $this->getOption('extension');

		// Cleanup
		(new Filesystem(new Local(JPATH_ROOT . '/cache')))
			->deleteDir('twig');

		$this->g11nUtil = new G11nUtil;
		$twigExtensions = [new TrackerExtension($this->getContainer())];

		if (!$reqExtension || $reqExtension == 'JTracker')
		{
			// Process core files
			$extension = 'JTracker';
			$domain    = 'Core';

			$this->out(sprintf(g11n3t('Processing: %1$s %2$s'), $domain, $extension));

			$templatePath = Storage::getTemplatePath($extension, $domain);

			$paths = [ExtensionHelper::getDomainPath($domain)];

			$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

			// Process core JS files

			$extension = 'core.js';
			$domain    = 'CoreJS';

			$this->out(sprintf(g11n3t('Processing: %1$s %2$s'), $domain, $extension));

			$templatePath = Storage::getTemplatePath('JTracker.js', 'Core');

			$paths = [ExtensionHelper::getDomainPath($domain)];

			$this->processTemplates($extension, $domain, 'js', $paths, $templatePath);

			// Process base template

			$extension = 'JTracker';
			$domain    = 'Template';

			$this->out(sprintf(g11n3t('Processing: %1$s %2$s'), $domain, $extension));

			$twigDir = JPATH_ROOT . '/cache/twig/JTracker';

			$this->g11nUtil->makePhpFromTwig(JPATH_ROOT . '/templates', JPATH_ROOT . '/templates', $twigDir, $twigExtensions);

			$templatePath = JPATH_ROOT . '/templates/' . $extension . '/' . ExtensionHelper::$langDirName . '/templates/' . $extension . '.pot';

			$paths = [ExtensionHelper::getDomainPath($domain)];

			$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

			$this->g11nUtil->replaceTwigPaths(JPATH_ROOT . '/templates', $twigDir, $templatePath, JPATH_ROOT);

			// Process the CLI application

			$extension = 'cli';
			$domain    = 'CLI';

			$this->out(sprintf(g11n3t('Processing: %1$s %2$s'), $domain, $extension));

			$templatePath = Storage::getTemplatePath($extension, $domain);

			$paths = [ExtensionHelper::getDomainPath($domain)];

			$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);
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

			$this->out(sprintf(g11n3t('Processing App: %s'), $extension));

			$domain = 'App';

			$this->g11nUtil->makePhpFromTwig(JPATH_ROOT . '/templates', JPATH_ROOT . '/templates/' . strtolower($extension), JPATH_ROOT . '/cache/twig/' . $extension, $twigExtensions, true);

			$templatePath = JPATH_ROOT . '/src/App/' . $extension . '/' . ExtensionHelper::$langDirName . '/templates/' . $extension . '.pot';

			$paths = [
				ExtensionHelper::getDomainPath($domain),
				JPATH_ROOT . '/src/App',
			];

			$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

			$this->g11nUtil->replaceTwigPaths(JPATH_ROOT . '/templates/' . strtolower($extension), JPATH_ROOT . '/cache/twig/' . $extension, $templatePath, JPATH_ROOT);
		}
	}

	/**
	 * Generate templates for an extension.
	 *
	 * @param   string  $extension     Extension name.
	 * @param   string  $domain        Extension domain.
	 * @param   string  $type          File extension.
	 * @param   array   $paths         Paths with source file.
	 * @param   string  $templatePath  The path to store the templates.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	protected function processTemplates($extension, $domain, $type, array $paths, $templatePath)
	{
		$packageName = 'JTracker';

		switch ($type)
		{
			case 'js':
				$excludes[] = '/jqplot/';
				$excludes[] = '/vendor/';
				$excludes[] = '/jquery-ui/';
				$excludes[] = '/validation';
				$excludes[] = 'vendor.js';
				$excludes[] = 'vendor.min.js';
				$excludes[] = 'jtracker-tmpl.js';
				$excludes[] = 'jtracker.min.js';
				break;

			case 'config':
				$excludes[] = '/templates/';
				$excludes[] = '/scripts/';
				break;

			default:
				$excludes   = [];
				break;
		}

		$template = (new LanguageTemplateType)
			->setExtension($extension)
			->setExtensionDir('core.js' === $extension ? '' : ExtensionHelper::getExtensionPath($extension))
			->setDomain($domain)
			->setType($type)
			->setPaths($paths)
			->setTemplatePath($templatePath)
			->setPackageName($packageName)
			->setPackageVersion($this->product->version)
			->setExcludes($excludes);

		$this->g11nUtil->processTemplates($template);

		// Manually strip the JROOT path - ...
		$contents = file_get_contents($templatePath);
		$contents = str_replace(JPATH_ROOT, '', $contents);

		// Manually strip unwanted information - ....
		$contents = str_replace('#, fuzzy', '', $contents);
		$contents = str_replace('"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\n"', '', $contents);

		// Set the character set
		$contents = str_replace('charset=CHARSET\n', 'charset=utf-8\n', $contents);

		// Some header data - that will hopefully remain..
		$contents = str_replace(
			'# SOME DESCRIPTIVE TITLE.',
			'# ' . $packageName . ' ' . $domain . ' ' . $extension . ' ' . $this->product->version,
			$contents
		);

		$contents = str_replace(
			'# Copyright (C) YEAR',
			'# Copyright (C) 2012 - ' . date('Y'),
			$contents
		);

		$contents = str_replace(
			'# This file is distributed under the same license as the PACKAGE package.',
			'# This file is distributed under the same license as the ' . $packageName . ' package.',
			$contents
		);

		file_put_contents($templatePath, $contents);

		$this->out(g11n3t('Your template has been created'));

		return $this;
	}
}
