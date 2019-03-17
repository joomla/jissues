<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

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
		$defined['npm']      = json_decode(file_get_contents(JPATH_ROOT . '/package.json'));
		$defined['credits']  = json_decode(file_get_contents(JPATH_ROOT . '/credits.json'));

		$installedComposer = json_decode(file_get_contents(JPATH_ROOT . '/vendor/composer/installed.json'));
		$installedNpm      = json_decode(file_get_contents(JPATH_ROOT . '/package-lock.json'));

		$this->product = $defined['composer'];

		foreach ($installedComposer as $entry)
		{
			$package = new \stdClass;

			$package->name        = $entry->name;
			$package->description = isset($entry->description) ? $entry->description : '';
			$package->version     = $entry->version;
			$package->sourceURL   = $entry->source->url;
			$package->sourceRef   = isset($entry->source->reference) ? $entry->source->reference : '';

			$packages['composer'][$entry->name] = $package;
		}

		foreach ($installedNpm->dependencies as $packageName => $packageData)
		{
			if (!isset($defined['npm']->dependencies->$packageName))
			{
				continue;
			}

			$package = new \stdClass;

			$package->name        = $packageName;
			$package->description = '';
			$package->version     = $packageData->version;
			$package->sourceURL   = '';

			$packages['npm'][$packageName] = $package;
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

		foreach ($defined['npm']->dependencies as $packageName => $version)
		{
			$installed = $packages['npm'][$packageName];

			$item = new \stdClass;

			$item->packageName = $packageName;
			$item->version     = $version;
			$item->installed   = $installed->version;
			$item->description = $installed->description ?: '';
			$item->sourceURL   = $installed->sourceURL ?: '';

			$sorted['javascript'][] = $item;
		}

		$sorted['credits'] = $defined['credits'];

		return $sorted;
	}
}
