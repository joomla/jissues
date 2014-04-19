<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

use g11n\Support\ExtensionHelper as g11nExtensionHelper;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

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
		$packages = array();
		$defined  = array();

		$defined['composer'] = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));
		$defined['bower']    = json_decode(file_get_contents(JPATH_ROOT . '/bower.json'));
		$defined['credits']  = json_decode(file_get_contents(JPATH_ROOT . '/credits.json'));

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
			$package->sourceURL   = isset($info->homepage) ? $info->homepage : 'N/A';

			$packages['bower'][$package->name] = $package;
		}

		$this->dependencies = $this->getSorted($defined, $packages);

		$contents = (new Mustache_Engine)
			->render(
				(new Mustache_Loader_FilesystemLoader(__DIR__ . '/tpl'))
					->load('depfile'),
				$this
			);

		$fileName = $this->getApplication()->input->getPath('file', $this->getApplication()->input->getPath('f'));

		if ($fileName)
		{
			$this->out('Writing contents to: ' . $fileName);

			file_put_contents($fileName, $contents);
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

		$sorted['lang-credits'] = $this->checkLanguageFiles();

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
		$list = array();

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

		$langTags = $this->getApplication()->get('languages');
		$noEmail = $this->getApplication()->input->get('noemail');

		foreach ($langTags as $langTag)
		{
			if ('en-GB' == $langTag)
			{
				continue;
			}

			$langInfo = new \stdClass;

			$langInfo->tag = $langTag;
			$langInfo->translators = array();

			$translators = array();

			foreach ($scopes as $domain => $extensions)
			{
				foreach ($extensions as $extension)
				{
					$path = g11nExtensionHelper::findLanguageFile($langTag, $extension, $domain);

					if (false == file_exists($path))
					{
						$this->out(sprintf('Language file not found %s, %s, %s' . $langTag, $extension, $domain));

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

						if (false == in_array($line, $translators))
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
