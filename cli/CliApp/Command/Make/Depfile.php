<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use CliApp\Application\CliApplication;
use CliApp\Command\TrackerCommandOption;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

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
	public $dependencies = array();

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Create and update a dependency file.';

	/**
	 * Target file name.
	 *
	 * @var    string
	 * @since  1.0
	 */
	private $fileName = '';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->addOption(
			new TrackerCommandOption(
				'file', 'f',
				'Write output to a file.'
			)
		);

		$this->fileName = $this->application->input->getPath('file', $this->application->input->getPath('f'));
	}

	/**
	 * Execute the command.
	 *
	 * @throws \Exception
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$packages = array();
		$defined  = array();

		$defined['composer'] = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
		$defined['bower']    = json_decode(file_get_contents(JPATH_ROOT . '/bower.json'));

		$installed = json_decode(file_get_contents(JPATH_ROOT . '/vendor/composer/installed.json'));

		$this->product = $defined['composer'];

		foreach ($installed as $entry)
		{
			$package = new \stdClass;

			$package->name        = $entry->name;
			$package->description = $entry->description;
			$package->version     = $entry->version;
			$package->sourceURL   = $entry->source->url;
			$package->sourceRef   = isset($entry->source->reference) ? $entry->source->reference : '';

			$packages['composer'][$entry->name] = $package;
		}

		foreach ($defined['bower']->dependencies as $packageName => $version)
		{
			$output = array();

			exec('bower info --json ' . $packageName . '#' . $version, $output);

			$info = json_decode(implode("\n", $output));

			$package = new \stdClass;

			$package->name        = $info->name;
			$package->description = isset($info->description) ? $info->description : '';
			$package->sourceURL   = $info->homepage;

			$packages['bower'][$package->name] = $package;
		}

		$this->dependencies = $this->getSorted($defined, $packages);

		$contents = with(new Mustache_Engine)
			->render(
				with(new Mustache_Loader_FilesystemLoader(__DIR__ . '/tpl'))
					->load('depfile'),
				$this
			);

		if ($this->fileName)
		{
			$this->out('Writing contents to: ' . $this->fileName);

			file_put_contents($this->fileName, $contents);
		}
		else
		{
			echo $contents;
		}

		$this->out()
			->out('Finished =;)');
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
		$sorted = array();

		foreach (array('require' => 'php', 'require-dev' => 'php-dev') as $sub => $section)
		{
			$items = array();

			foreach ($defined['composer']->$sub as $packageName => $version)
			{
				if ('php' == $packageName)
				{
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
					$package = $packages['composer'][$packageName];

					$item->description = $package->description;
					$item->installed   = $package->version;
					$item->sourceURL   = $package->sourceURL;

					if ('dev-master' == $package->version)
					{
						$item->sourceRef = $package->sourceRef;
					}
				}

				$items[] = $item;
			}

			$sorted[$section] = $items;
		}

		foreach ($defined['bower']->dependencies as $packageName => $version)
		{
			$package = $packages['bower'][$packageName];

			$item = new \stdClass;

			$item->packageName = $packageName;
			$item->version     = $version;
			$item->description = '';
			$item->sourceURL   = $package->sourceURL;

			if ($package->description)
			{
				$item->description = $package->description;
			}

			$sorted['javascript'][] = $item;
		}

		return $sorted;
	}
}
