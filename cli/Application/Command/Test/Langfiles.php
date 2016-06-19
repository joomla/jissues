<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use g11n\Language\Storage;
use g11n\Support\ExtensionHelper;

use JTracker\Helper\LanguageHelper;

use PHP_CodeSniffer_File;

/**
 * Class for checking language files.
 *
 * @since  1.0
 */
class Langfiles extends Test
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Check language files';

	/**
	 * Execute the command.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Check language files');

		LanguageHelper::addDomainPaths();

		$languages = $this->getApplication()->get('languages');

		$languages[] = 'templates';

		$errors = false;

		foreach (LanguageHelper::getScopes() as $domain => $extensions)
		{
			foreach ($extensions as $extension)
			{
				$scopePath     = ExtensionHelper::getDomainPath($domain);
				$extensionPath = ExtensionHelper::getExtensionLanguagePath($extension);

				foreach ($languages as $language)
				{
					$path = $scopePath . '/' . $extensionPath . '/' . $language;

					$path .= ('templates' == $language)
						?  '/' . $extension . '.pot'
						:  '/' . $language . '.' . $extension . '.po';

					$this->debugOut(sprintf('Check: %s-%s %s in %s', $domain, $extension, $language, $path));

					if (false == file_exists($path))
					{
						$this->debugOut('not found');

						continue;
					}

					// Check if the language file has UNIX style line endings.
					if ("\n" != PHP_CodeSniffer_File::detectLineEndings($path))
					{
						$this->out($path)
							->out('<error> The file does not have UNIX style line endings ! </error>')
							->out();

						continue;
					}

					// Check the language file for errors.
					$output = shell_exec('msgfmt -c ' . $path . ' 2>&1');

					if ($output)
					{
						// If the command produces any output, that means errors.
						$errors = true;
						$this->out($output);
					}
					else
					{
						$this->debugOut('ok');
					}
				}
			}
		}

		$this->out(
			$errors
			? '<error> There have been errors. </error>'
			: '<ok>Language file syntax OK</ok>'
		);

		if ($this->exit)
		{
			exit($errors ? 1 : 0);
		}

		return ($errors ? 1 : 0);
	}
}
