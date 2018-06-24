<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

use ElKuKu\G11n\Support\ExtensionHelper as g11nExtensionHelper;

use JTracker\Helper\LanguageHelper;

use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Class for generating a dependency file.
 *
 * @since  1.0
 */
class Depfile extends Make
{
	/**
	 * Product object.
	 *
	 * @var    object
	 * @since  1.0
	 */
	public $product = null;

	/**
	 * Dependencies.
	 *
	 * @var    array
	 * @since  1.0
	 */
	public $dependencies = [];

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Create and update a dependency file.');

		$this->addOption(
			new TrackerCommandOption(
				'file', 'f',
				g11n3t('Write output to a file.')
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
		$packages = [];
		$defined  = [];

		$defined['composer'] = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
		$defined['bower']    = json_decode(file_get_contents(JPATH_ROOT . '/bower.json'));
		$defined['credits']  = json_decode(file_get_contents(JPATH_ROOT . '/credits.json'));

		$installed = json_decode(file_get_contents(JPATH_ROOT . '/vendor/composer/installed.json'));

		$this->product = $defined['composer'];

		foreach ($installed as $entry)
		{
			$package = new \stdClass;

			$package->name        = $entry->name;
			$package->description = isset($entry->description) ? $entry->description : '';
			$package->version     = $entry->version;
			$package->sourceURL   = $entry->source->url;
			$package->sourceRef   = isset($entry->source->reference) ? $entry->source->reference : '';

			$packages['composer'][$entry->name] = $package;
		}

		foreach ($defined['bower']->dependencies as $packageName => $version)
		{
			$output = [];

			exec('bower info --json ' . $packageName . '#' . $version, $output);

			$info = json_decode(implode("\n", $output));

			$package = new \stdClass;

			$package->name        = $info->name;
			$package->description = isset($info->description) ? $info->description : '';
			$package->sourceURL   = isset($info->homepage) ? $info->homepage : 'N/A';

			$packages['bower'][$packageName] = $package;
		}

		$this->dependencies = $this->getSorted($defined, $packages);

		$twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__ . '/tpl'));

		$twig->setCache(false);

		$contents = $twig->render(
			'dependencies.twig',
			['dependencies' => $this->dependencies, 'product' => $this->product]
		);

		$fileName = $this->getOption('file');

		if ($fileName)
		{
			$this->out(sprintf(g11n3t('Writing contents to: %s'), $fileName));

			file_put_contents($fileName, $contents);
		}
		else
		{
			echo $contents;
		}

		$this->out()
			->out(g11n3t('Finished.'));
	}

	/**
	 * Generate HTML output.
	 *
	 * @param   array  $defined   List of defined packages
	 * @param   array  $packages  List of installed packages
	 *
	 * @return  string  HTML output
	 *
	 * @since   1.0
	 */
	private function getSorted(array $defined, array $packages)
	{
		$sorted = [];

		foreach (['require' => 'php', 'require-dev' => 'php-dev'] as $sub => $section)
		{
			$items = [];

			foreach ($defined['composer']->$sub as $packageName => $version)
			{
				if ('php' == $packageName)
				{
					$o = new \stdClass;
					$o->version = $version;

					$sorted['php-version'] = $o;
					$sorted['php-version'] = $version;

					continue;
				}

				$item              = new \stdClass;
				$item->packageName = $packageName;
				$item->version     = $version;
				$item->installed   = '';
				$item->description = '';
				$item->sourceRef   = '';
				$item->sourceURL   = '';

				if (isset($packages['composer'][$packageName]))
				{
					$item->description = $packages['composer'][$packageName]->description;
					$item->installed   = $packages['composer'][$packageName]->version;
					$item->sourceURL   = $packages['composer'][$packageName]->sourceURL;

					if ('dev-master' == $packages['composer'][$packageName]->version)
					{
						$item->sourceRef = $packages['composer'][$packageName]->sourceRef;
					}
				}

				$items[] = $item;
			}

			$sorted[$section] = $items;
		}

		foreach ($defined['bower']->dependencies as $packageName => $version)
		{
			$installed = $packages['bower'][$packageName];

			$item = new \stdClass;

			$item->packageName = $packageName;
			$item->version     = $version;
			$item->description = '';
			$item->sourceURL   = $installed->sourceURL;

			if ($installed->description)
			{
				$item->description = $installed->description;
			}

			$sorted['javascript'][] = $item;
		}

		$sorted['credits'] = $defined['credits'];

		if (false)
		{
			/*
			 * @todo Translator credits are disabled
			 * Our current translation service provider "Crowdin" does not support translator credits (yet).
			 */
			$sorted['lang-credits'] = $this->checkLanguageFiles();
		}

		return $sorted;
	}

	/**
	 * Extract information about the translators from the language files.
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	private function checkLanguageFiles()
	{
		$list = [];

		LanguageHelper::addDomainPaths();

		$langTags = LanguageHelper::getLanguageCodes();
		$noEmail = $this->getOption('noemail');

		foreach ($langTags as $langTag)
		{
			if ('en-GB' == $langTag)
			{
				continue;
			}

			$langInfo = new \stdClass;

			$langInfo->tag = $langTag;
			$langInfo->translators = [];

			$translators = [];

			foreach (LanguageHelper::getScopes() as $domain => $extensions)
			{
				foreach ($extensions as $extension)
				{
					$path = g11nExtensionHelper::findLanguageFile($langTag, $extension, $domain);

					if (false === file_exists($path))
					{
						$this->out(
							g11n3t(
								'Language file not found: %tag%, %extension%, %domain%',
								['%tag%' => $langTag, '%domain%' => $domain, '%extension%' => $extension]
							)
						);

						continue;
					}

					$f = fopen($path, 'r');

					$line = '#';
					$started = false;

					while ($line)
					{
						$line = fgets($f, 1000);

						if (0 !== strpos($line, '#'))
						{
							// Encountered the first line - We're done parsing.
							$line = '';

							continue;
						}

						if (strpos($line, 'Translators:'))
						{
							// Start
							$started = true;

							continue;
						}

						if (!$started)
						{
							continue;
						}

						$line = trim($line, "# \n");

						if ($noEmail)
						{
							// Strip off the e-mail address
							// Format: '<name@domain.tld>, '
							$line = preg_replace('/<[a-z0-9\.\-+]+@[a-z\.]+>,\s/i', '', $line);
						}

						if (false === in_array($line, $translators))
						{
							$translators[] = $line;
						}
					}

					fclose($f);
				}
			}

			foreach ($translators as $translator)
			{
				$t = new \stdClass;

				$t->translator = $translator;

				$langInfo->translators[] = $t;
			}

			$list[] = $langInfo;
		}

		return $list;
	}
}
