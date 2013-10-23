<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use CliApp\Application\CliApplication;

use g11n\g11n;
use g11n\Language\Storage;
use g11n\Support\ExtensionHelper;
use g11n\Support\FileInfo;
use g11n\Support\TransInfo;

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
	 * Constructor.
	 *
	 * @param   CliApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(CliApplication $application)
	{
		$this->application = $application;
		$this->description = 'Create language file templates.';
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
		$this->application->outputTitle('Make Language templates');

		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/cache/twig');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/cache/twig');

		defined('JDEBUG') || define('JDEBUG', 0);

		// Cleanup
		$this->delTree(JPATH_ROOT . '/cache/twig');

		// Process core files
		$extension = 'JTracker';
		$domain    = 'Core';

		$this->out('Processing: ' . $domain . ' ' . $extension);

		$templatePath = Storage::getTemplatePath($extension, $domain);

		$paths = array(ExtensionHelper::getDomainPath($domain));

		$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

		// Process base template

		$extension = 'JTracker';
		$domain    = 'Template';

		$this->out('Processing: ' . $domain . ' ' . $extension);

		$twigDir = JPATH_ROOT . '/cache/twig/JTracker';

		$this->makePhpFromTwig(JPATH_ROOT . '/templates', $twigDir);

		$templatePath = JPATH_ROOT . '/templates/' . $extension . '/' . ExtensionHelper::$langDirName . '/templates/' . $extension . '.pot';

		$paths = array(ExtensionHelper::getDomainPath($domain));

		$this->processTemplates($extension, $domain, 'php', $paths, $templatePath);

		$this->replacePaths(JPATH_ROOT . '/templates', $twigDir, $templatePath);

		// Process App templates

		/* @type \DirectoryIterator $fileInfo */
		foreach (new \DirectoryIterator(JPATH_ROOT . '/src/App') as $fileInfo)
		{
			if ($fileInfo->isDot())
			{
				continue;
			}

			$extension = $fileInfo->getFileName();

			$this->out('Processing: ' . $extension);

			$domain = 'App';

			$this->makePhpFromTwig(JPATH_ROOT . '/templates/' . strtolower($extension), JPATH_ROOT . '/cache/twig/' . $extension);

			$templatePath = JPATH_ROOT . '/src/App/' . $extension . '/' . ExtensionHelper::$langDirName . '/templates/' . $extension . '.pot';

			$paths = array(
				ExtensionHelper::getDomainPath($domain),
				JPATH_ROOT . '/src/App'
			);

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
	 * @throws \Exception
	 * @return $this
	 */
	protected function processTemplates($extension, $domain, $type, array $paths, $templatePath)
	{
		$headerData = '';
		$headerData .= ' --copyright-holder="JTracker(C)"';
		$headerData .= ' --package-name="' . $extension . ' - ' . $domain . '"';
		$headerData .= ' --package-version="123.456"';
		$headerData .= ' --msgid-bugs-address="info@example.com"';

		$comments = ' --add-comments=TRANSLATORS:';

		$keywords = ' -k --keyword=g11n3t --keyword=g11n4t:1,2';
		$forcePo  = ' --force-po --no-wrap';

		$extensionDir = ExtensionHelper::getExtensionPath($extension);
		$dirName      = dirname($templatePath);

		$cleanFiles = array();
		$excludes   = array();

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

		$buildOpts = '';

		switch ($type)
		{
			case 'js':
				$buildOpts .= ' -L python';
				break;

			case 'config':
				$excludes[] = '/templates/';
				$excludes[] = '/scripts/';
				break;

			default:
				break;
		}

		$this->debugOut(sprintf('Found %d files', count($cleanFiles)));

		if ('config' == $subType)
		{
			$this->processConfigFiles($cleanFiles, $templatePath);
		}
		else //
		{
			$fileList = implode("\n", $cleanFiles);

			$command = $keywords . $buildOpts . ' -o ' . $templatePath . $forcePo . $comments . $headerData;

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

		file_put_contents($templatePath, $contents);

		$this->out('Your template has been created');

		return $this;
	}

	/**
	 * Get the source files to process.
	 *
	 * @param   string  $path      The base path.
	 * @param   string  $search    The file extension to search for.
	 * @param   array   $excludes  Files to exclude.
	 *
	 * @return array
	 */
	private function getCleanFiles($path, $search, $excludes)
	{
		$cleanFiles = array();

		/* @type \SplFileInfo $fileInfo */
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $fileInfo)
		{
			if ($fileInfo->getExtension() != $search)
			{
				continue;
			}

			$excluded = false;

			foreach ($excludes as $exclude)
			{
				if (strpos($fileInfo->getFilename(), $exclude))
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
	 * @since  1.0
	 * @throws \Exception
	 * @return $this
	 */
	private function processConfigFiles($cleanFiles, $templatePath)
	{
		defined('NL') || define('NL', "\n");
		$parser    = g11n::getCodeParser('xml');
		$potParser = g11n::getLanguageParser('pot');

		$options = new \stdClass;

		$outFile = new FileInfo;

		foreach ($cleanFiles as $fileName)
		{
			$fileInfo = $parser->parse($fileName);

			if (!count($fileInfo->strings))
			{
				continue;
			}

			$relPath = str_replace(JPATH_ROOT . DS, '', $fileName);

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
	 * @param   string  $twigDir   Path to twig templates.
	 * @param   string  $cacheDir  Path to cache dir.
	 *
	 * @since  1.0
	 * @return $this
	 */
	protected function makePhpFromTwig($twigDir, $cacheDir)
	{
		$loader = new Twig_Loader_Filesystem(array(JPATH_ROOT . '/templates', $twigDir));

		// Force auto-reload to always have the latest version of the template
		$twig = new Twig_Environment(
			$loader,
			array(
				'cache'       => $cacheDir,
				'auto_reload' => true
			)
		);

		// Configure Twig the way you want
		$twig->addExtension(new TrackerExtension);

		// Iterate over all your templates
		/* @type \DirectoryIterator $file */
		foreach (new \DirectoryIterator($twigDir) as $file)
		{
			// Force compilation
			if ($file->isFile())
			{
				$twig->loadTemplate(str_replace($twigDir . '/', '', $file));
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
	 * @return $this
	 *
	 * @throws \RuntimeException
	 *
	 * @since  1.0
	 */
	private function replacePaths($sourcePath, $twigPath, $templateFile)
	{
		$pathMap = array();

		/* @type \DirectoryIterator $fileInfo */
		foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($twigPath)) as $fileInfo)
		{
			if ('php' == $fileInfo->getExtension())
			{
				$f = new \stdClass;

				$f->twigPhpPath = str_replace(JPATH_ROOT, '', $fileInfo->getPathname());
				$f->lines = file($fileInfo->getPathname());

				if (false == isset($f->lines[2]) || false == preg_match('/([A-z0-9\.\-]+)/', $f->lines[2], $matches))
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

				if (false == array_key_exists($path, $pathMap))
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
	 * @return bool
	 *
	 * @since  1.0
	 */
	private function delTree($dir)
	{
		$files = array_diff(scandir($dir), array('.', '..'));

		foreach ($files as $file)
		{
			(is_dir($dir . '/' . $file))
				? $this->delTree($dir . '/' . $file)
				: unlink($dir . '/' . $file);
		}

		return rmdir($dir);
	}
}
