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
use League\Flysystem\Plugin\ListPaths;

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
	 * List of known languages.
	 *
	 * @var array
	 */
	private static $languages = [
		'ca-ES' => [
			'iso' => 'cat',
			'name' => 'Catalan',
			'display' => 'Català'
		],
		'da-DK' => [
			'iso' => 'dk',
			'name' => 'Danish',
			'display' => 'Dansk'
		],
		'de-DE' => [
			'iso' => 'de',
			'name' => 'German',
			'display' => 'Deutsch'
		],
		'en-GB' => [
			'iso' => 'uk',
			'name' => 'English',
			'display' => 'English'
		],
		'es-ES' => [
			'iso' => 'es',
			'name' => 'Spanish',
			'display' => 'Español'
		],
		'et-EE' => [
			'iso' => 'ee',
			'name' => 'Estonian',
			'display' => 'Eesti'
		],
		'fr-FR' => [
			'iso' => 'fr',
			'name' => 'French',
			'display' => 'Français'
		],
		'hu-HU' => [
			'iso' => 'hu',
			'name' => 'Hungarian',
			'display' => 'Magyar'
		],
		'it-IT' => [
			'iso' => 'it',
			'name' => 'Italian',
			'display' => 'Italiano'
		],
		'lv-LV' => [
			'iso' => 'lv',
			'name' => 'Latvian',
			'display' => 'Latviešu valoda'
		],
		'nb-NO' => [
			'iso' => 'no',
			'name' => 'Norwegian',
			'display' => 'Norsk'
		],
		'nl-NL' => [
			'iso' => 'nl',
			'name' => 'Dutch',
			'display' => 'Nederlands'
		],
		'pl-PL' => [
			'iso' => 'pl',
			'name' => 'Polish',
			'display' => 'Język polski'
		],
		'pt-BR' => [
			'iso' => 'br',
			'name' => 'Portuguese Brazil',
			'display' => 'Português Brazil'
		],
		'pt-PT' => [
			'iso' => 'pt',
			'name' => 'Portuguese',
			'display' => 'Português'
		],
		'ro-RO' => [
			'iso' => 'ro',
			'name' => 'Romanian',
			'display' => 'Limba română'
		],
		'ru-RU' => [
			'iso' => 'ru',
			'name' => 'Russian',
			'display' => 'Русский'
		],
		'zh-CN' => [
			'iso' => 'cn',
			'name' => 'Chinese',
			'display' => '中文 (Zhōngwén)'
		]
	];

	/**
	 * Get a language tag by code.
	 *
	 * @param   string  $languageCode  The language code.
	 *
	 * @return string
	 */
	public static function getLanguageTagByCode($languageCode)
	{
		return  array_key_exists($languageCode, static::$languages) ? static::$languages[$languageCode]['iso'] : '';
	}

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
			'App' => (new Filesystem(new Local(JPATH_ROOT . '/src/App')))->addPlugin(new ListPaths)->listPaths()
		];
	}

	/**
	 * Get an array with language codes (e.g. en-GB)
	 *
	 * @return array
	 */
	public static function getLanguageCodes()
	{
		return array_keys(self::$languages);
	}

	/**
	 * Get an array containing information about languages.
	 *
	 * @return array
	 */
	public static function getLanguages()
	{
		return self::$languages;
	}

	/**
	 * Get an array containing information about languages.
	 * Sorted by display name.
	 *
	 * @return array
	 */
	public static function getLanguagesSortedByDisplayName()
	{
		$languages = self::$languages;

		uasort(
			$languages, function($a, $b)
			{
				return strcmp($a['display'], $b['display']);
			}
		);

		return $languages;
	}
}
