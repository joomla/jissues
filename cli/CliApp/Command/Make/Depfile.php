<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use Joomla\DI\Container;

/**
 * Class for generating a dependency file.
 *
 * @since  1.0
 */
class Depfile extends Make
{
	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		$this->application = $container->get('app');
		$this->description = 'Create and update a dependency file.';
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
		$defined = array();

		$defined['composer'] = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
		$defined['bower']    = json_decode(file_get_contents(JPATH_ROOT . '/bower.json'));

		$installed = json_decode(file_get_contents(JPATH_ROOT . '/vendor/composer/installed.json'));

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

		// @todo write to a file
		$this->out($this->getOutput($defined, $packages));

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
	private function getOutput(array $defined, array $packages)
	{
		$output = array();

		$subs = array('Production' => 'require', 'Development' => 'require-dev');

		$product = $defined['composer'];

		$output[] = sprintf('# Dependencies for %s %s', $product->name, $product->version);
		$output[] = '';
		$output[] = $product->description;
		$output[] = '';
		$output[] = '* Source URL: ' . $product->homepage;
		$output[] = '';

		foreach ($subs as $title => $sub)
		{
			$output[] = '## PHP - ' . $title;
			$output[] = '';

			foreach ($defined['composer']->$sub as $packageName => $version)
			{
				$output[] = sprintf('#### %s (%s)', $packageName, $version);
				$output[] = '';

				if (isset($packages['composer'][$packageName]))
				{
					$package = $packages['composer'][$packageName];

					$output[] = $package->description;
					$output[] = '';
					$output[] = '* Installed: ' . $package->version;

					if ('dev-master' == $package->version)
					{
						$output[] = '* Ref.: ' . $package->sourceRef;
					}

					$output[] = '* Source URL: ' . $package->sourceURL;
					$output[] = '';
				}

			}
		}

		$output[] = '## JavaScript';
		$output[] = '';

		foreach ($defined['bower']->dependencies as $packageName => $version)
		{
			$package = $packages['bower'][$packageName];

			$output[] = sprintf('#### %s (%s)', $packageName, $version);
			$output[] = '';

			if ($package->description)
			{
				$output[] = $package->description;
				$output[] = '';
			}

			$output[] = '* Source URL: ' . $package->sourceURL;
			$output[] = '';
		}

		return implode("\n", $output);
	}
}
