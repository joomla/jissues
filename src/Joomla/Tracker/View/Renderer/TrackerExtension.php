<?php
/**
 * @copyright  Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View\Renderer;

use Joomla\Factory;
use Joomla\Language\Text;

use Joomla\Tracker\Application\TrackerApplication;

/**
 * Tracker Twig extension class.
 *
 * @since  1.0
 */
class TrackerExtension extends \Twig_Extension
{
	/**
	 * Returns the name of the extension.
	 *
	 * @return  string  The extension name.
	 *
	 * @since   1.0
	 */
	public function getName()
	{
		return 'tracker';
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return  array  An array of global variables.
	 *
	 * @since   1.0
	 */
	public function getGlobals()
	{
		/* @var TrackerApplication $app */
		$app = Factory::$application;

		return array(
			'uri'    => $app->get('uri'),
			'jdebug' => JDEBUG,
		);
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return  array  An array of filters.
	 *
	 * @since   1.0
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('base', 'basename'),
			new \Twig_SimpleFilter('typeof', 'get_class'),
		);
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  array  An array of functions.
	 *
	 * @since   1.0
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('translate', array($this, 'translate')),
		);
	}

	/**
	 * Twig template function to translate a string into the current language.
	 *
	 * @param   string  $string  The string to translate.
	 *
	 * @return  string  The translated string.
	 *
	 * @since   1.0
	 */
	public function translate($string)
	{
		return Text::_($string);
	}
}
