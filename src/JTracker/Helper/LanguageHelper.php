<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Helper;

use g11n\Support\ExtensionHelper;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Helper class containing methods for working with languages.
 *
 * @since  1.0
 */
abstract class LanguageHelper
{
	/**
	 * List of exceptions to the standard of Crowdin language tags.
	 *
	 * @var array
	 */
	private static $knownCrowdinExceptions = [
		'es-ES' => 'es-ES',
		'nb-NO' => 'no',
		'pt-BR' => 'pt-BR',
		'pt-PT' => 'pt-PT',
		'zh-CN' => 'zh-CN'
	];

	/**
	 * Get a valid Crowdin language tag.
	 *
	 * @param   string  $language  The "normal" language tag.
	 *
	 * @return string
	 */
	public static function getCrowdinLanguageTag($language)
	{
		return  array_key_exists($language, static::$knownCrowdinExceptions) ? static::$knownCrowdinExceptions[$language] : substr($language, 0, 2);
	}

	/**
	 * Add domain paths for the application.
	 *
	 * @return void
	 */
	public static function addDomainPaths()
	{
		ExtensionHelper::addDomainPath('Core', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('CoreJS', JPATH_ROOT . '/src');
		ExtensionHelper::addDomainPath('Template', JPATH_ROOT . '/templates');
		ExtensionHelper::addDomainPath('App', JPATH_ROOT . '/src/App');
		ExtensionHelper::addDomainPath('CLI', JPATH_ROOT);
	}

	/**
	 * Get an array of known application scopes.
	 *
	 * @return array
	 */
	public static function getScopes()
	{
		return [
			'Core' => ['JTracker'],
			'CoreJS' => ['JTracker.js'],
			'Template' => ['JTracker'],
			'CLI' => ['cli'],
			'App' => (new Filesystem(new Local(JPATH_ROOT . '/src/App')))->listPaths()
		];
	}
}
