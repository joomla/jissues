<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use Application\Command\TrackerCommandOption;

use ElKuKu\G11n\G11n;
use ElKuKu\G11n\Language\Storage;
use ElKuKu\G11n\Support\ExtensionHelper;
use ElKuKu\G11n\Support\FileInfo;
use ElKuKu\G11n\Support\TransInfo;

use JTracker\View\Renderer\TrackerExtension;

use Twig_Environment;
use Twig_Loader_Filesystem;

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
		$this->delTree(JPATH_ROOT . '/cache/twig');

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

			$this->makePhpFromTwig(JPATH_ROOT . '/templates', $twigDir);

			$templatePath = JPATH_ROOT . '/templates/' . $extension . '/' . ExtensionHelper::$langDirName . '/templates/' . $extension . '.pot';

			$paths = [ExtensionHelper::getDomainPath($domain)];

			$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

			$this->replacePaths(JPATH_ROOT . '/templates', $twigDir, $templatePath);

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

			$this->makePhpFromTwig(JPATH_ROOT . '/templates/' . strtolower($extension), JPATH_ROOT . '/cache/twig/' . $extension, true);

			$templatePath = JPATH_ROOT . '/src/App/' . $extension . '/' . ExtensionHelper::$langDirName . '/templates/' . $extension . '.pot';

			$paths = [
				ExtensionHelper::getDomainPath($domain),
				JPATH_ROOT . '/src/App',
			];

			$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

			$this->replacePaths(JPATH_ROOT . '/templates/' . strtolower($extension), JPATH_ROOT . '/cache/twig/' . $extension, $templatePath);
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

		$headerData = '';
		$headerData .= ' --copyright-holder="' . $packageName . '"';
		$headerData .= ' --package-name="' . $packageName . '"';
		$headerData .= ' --package-version="' . $this->product->version . '"';

		// @$headerData .= ' --msgid-bugs-address="info@example.com"';

		$comments = ' --add-comments=TRANSLATORS:';

		$keywords = ' -k --keyword=g11n3t --keyword=g11n4t:1,2';
		$noWrap   = ' --no-wrap';

		// Always write an output file even if no message is defined.
		$forcePo = ' --force-po';

		// Sort output by file location.
		$sortByFile = ' --sort-by-file';

		$extensionDir = $extension !== 'core.js' ? ExtensionHelper::getExtensionPath($extension) : '';
		$dirName      = dirname($templatePath);

		$cleanFiles = [];
		$excludes   = [];

		$buildOpts = '';

		switch ($type)
		{
			case 'js':
				$buildOpts .= ' -L python';
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
				break;
		}

		foreach ($paths as $base)
		{
			if (!is_dir($base . '/' . $extensionDir))
			{
				throw new \Exception('Invalid extension');
			}

			$cleanFiles = array_merge($cleanFiles, $this->getCleanFiles($base . '/' . $extensionDir, $type, $excludes));
		}

		if (!is_dir($dirName))
		{
			if (!mkdir($dirName, 0755, true))
			{
				throw new \Exception('Can not create the language template folder');
			}
		}

		$subType = '';

		if (strpos($extension, '.'))
		{
			$subType = substr($extension, strpos($extension, '.') + 1);
		}

		$this->debugOut(sprintf('Found %d files', count($cleanFiles)));

		if ('config' == $subType)
		{
			$this->processConfigFiles($cleanFiles, $templatePath);
		}
		else
		{
			$fileList = implode("\n", $cleanFiles);

			$command = $keywords . $buildOpts
				. ' -o ' . $templatePath
				. $forcePo
				. $noWrap
				. $sortByFile
				. $comments
				. $headerData;

			$this->debugOut($command);

			ob_start();

			system('echo "' . $fileList . '" | xgettext ' . $command . ' -f - 2>&1');

			$result = ob_get_clean();

			$this->out($result);
		}

		if (!file_exists($templatePath))
		{
			throw new \Exception('Could not create the template');
		}

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

	/**
	 * Get the source files to process.
	 *
	 * @param   string  $path      The base path.
	 * @param   string  $search    The file extension to search for.
	 * @param   array   $excludes  Files to exclude.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	private function getCleanFiles($path, $search, $excludes)
	{
		$cleanFiles = [];

		/** @var \SplFileInfo $fileInfo */
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $fileInfo)
		{
			if ($fileInfo->getExtension() != $search)
			{
				continue;
			}

			$excluded = false;

			foreach ($excludes as $exclude)
			{
				if (false !== strpos($fileInfo->getPathname(), $exclude))
				{
					$excluded = true;
				}
			}

			if (!$excluded)
			{
				$cleanFiles[] = $fileInfo->getRealPath();
				$this->debugOut('Found: ' . $fileInfo->getRealPath());
			}
		}

		return $cleanFiles;
	}

	/**
	 * Process config files in XML format.
	 *
	 * @param   array   $cleanFiles    Source files to process.
	 * @param   string  $templatePath  The path to store the template.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \Exception
	 */
	private function processConfigFiles($cleanFiles, $templatePath)
	{
		defined('NL') || define('NL', "\n");
		$parser    = G11n::getCodeParser('xml');
		$potParser = G11n::getLanguageParser('pot');

		$options = new \stdClass;

		$outFile = new FileInfo;

		foreach ($cleanFiles as $fileName)
		{
			$fileInfo = $parser->parse($fileName);

			if (!count($fileInfo->strings))
			{
				continue;
			}

			$relPath = str_replace(JPATH_ROOT . '/', '', $fileName);

			foreach ($fileInfo->strings as $key => $strings)
			{
				foreach ($strings as $string)
				{
					if (array_key_exists($string, $outFile->strings))
					{
						if (strpos($outFile->strings[$string]->info, $relPath . ':' . $key) !== false)
						{
							continue;
						}

						$outFile->strings[$string]->info .= '#: ' . $relPath . ':' . $key . NL;

						continue;
					}

					$t = new TransInfo;
					$t->info .= '#: ' . $relPath . ':' . $key . NL;
					$outFile->strings[$string] = $t;
				}
			}
		}

		$buffer = $potParser->generate($outFile, $options);

		if (!file_put_contents($templatePath, $buffer))
		{
			throw new \Exception('Unable to write the output file');
		}

		return $this;
	}

	/**
	 * Compile twig templates to PHP.
	 *
	 * @param   string   $twigDir    Path to twig templates.
	 * @param   string   $cacheDir   Path to cache dir.
	 * @param   boolean  $recursive  Scan the directory recursively.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function makePhpFromTwig($twigDir, $cacheDir, $recursive = false)
	{
		$loader = new Twig_Loader_Filesystem([JPATH_ROOT . '/templates', $twigDir]);

		// Force auto-reload to always have the latest version of the template
		$twig = new Twig_Environment(
			$loader,
			[
				'cache'       => $cacheDir,
				'auto_reload' => true,
			]
		);

		// Configure Twig the way you want
		$twig->addExtension(new TrackerExtension($this->getContainer()));

		// Iterate over all the templates
		if ($recursive)
		{
			/** @var \DirectoryIterator $file */
			foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($twigDir)) as $file)
			{
				// Force compilation
				if ($file->isFile())
				{
					$twig->loadTemplate(str_replace($twigDir . '/', '', $file));
				}
			}
		}
		else
		{
			/** @var \DirectoryIterator $file */
			foreach (new \DirectoryIterator($twigDir) as $file)
			{
				// Force compilation
				if ($file->isFile())
				{
					$twig->loadTemplate(str_replace($twigDir . '/', '', $file));
				}
			}
		}

		return $this;
	}

	/**
	 * Replace a compiled twig template path with the real path.
	 *
	 * @param   string  $sourcePath    Path to the twig sources.
	 * @param   string  $twigPath      Path to the compiled twig files.
	 * @param   string  $templateFile  Path to the template file.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	private function replacePaths($sourcePath, $twigPath, $templateFile)
	{
		$pathMap = [];

		/** @var \DirectoryIterator $fileInfo */
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($twigPath)) as $fileInfo)
		{
			if ('php' == $fileInfo->getExtension())
			{
				$f = new \stdClass;

				$f->twigPhpPath = str_replace(JPATH_ROOT, '', $fileInfo->getPathname());
				$f->lines = file($fileInfo->getPathname());

				if (false === isset($f->lines[2]) || false === preg_match('| ([A-z0-9\.\-\/]+)|', $f->lines[2], $matches))
				{
					throw new \RuntimeException('Can not parse the twig template at: ' . $fileInfo->getPathname());
				}

				$f->twigTwigPath = str_replace(JPATH_ROOT, '', $sourcePath) . '/' . $matches[1];

				$pathMap[$f->twigPhpPath] = $f;
			}
		}

		$lines = file($templateFile);

		foreach ($lines as $cnt => $line)
		{
			if (preg_match('/#: ([A-z0-9\/\.]+):([0-9]+)/', $line, $matches))
			{
				$path = $matches[1];
				$lineNo = $matches[2];

				if (false === array_key_exists($path, $pathMap))
				{
					// Not a twig template
					continue;
				}

				$twigPhp = $pathMap[$path];

				$matches = null;

				for ($i = $lineNo - 2; $i >= 0; $i --)
				{
					$pLine = $twigPhp->lines[$i];

					if (preg_match('#// line ([0-9]+)#', $pLine, $matches))
					{
						break;
					}
				}

				if (!$matches)
				{
					throw new \RuntimeException('Can not fetch the line number in: ' . $line);
				}

				$lines[$cnt] = '#: ' . $twigPhp->twigTwigPath . ':' . $matches[1] . "\n";
			}
		}

		file_put_contents($templateFile, implode('', $lines));

		return $this;
	}

	/**
	 * Delete a directory recursively.
	 *
	 * @param   string  $dir  The directory to delete.
	 *
	 * @return  boolean
	 *
	 * @since   1.0
	 */
	private function delTree($dir)
	{
		if (false === is_dir($dir))
		{
			// Directory does not exist.
			return true;
		}

		$files = array_diff(scandir($dir), ['.', '..']);

		foreach ($files as $file)
		{
			(is_dir($dir . '/' . $file))
				? $this->delTree($dir . '/' . $file)
				: unlink($dir . '/' . $file);
		}

		return rmdir($dir);
	}
}
