<?php
/**
 * @copyright  Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View\Renderer;

use Joomla\Language\Text;

/**
 * JTracker Twig extension class.
 *
 * @package  JTracker\View\Renderer
 *
 * @since    1.0
 */
class TrackerExtension extends \Twig_Extension
{
	/**
	 * Returns the name of the extension.
	 *
	 * @return  string  The extension name.
	 */
	public function getName()
	{
		return 'tracker';
	}

	/**
	 * Returns a list of global variables to add to the existing list.
	 *
	 * @return  array  An array of global variables.
	 */
	public function getGlobals()
	{
		return array(
			'www'    => JPATH_THEMES,
			'jdebug' => JDEBUG,
		);
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  array  An array of functions.
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('translate', array($this, 'translate')),
			new \Twig_SimpleFunction('stripJRoot', array($this, 'stripJRoot')),
		);
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return array An array of filters
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('basename', 'basename'),
			new \Twig_SimpleFilter('get_class', 'get_class'),
			new \Twig_SimpleFilter('stripJRoot', array($this, 'stripJRoot')),
		);
	}

	/**
	 * Translate a string using Joomla\Text.
	 *
	 * @param   string  $string  The string to translate.
	 *
	 * @return string
	 */
	public function translate($string)
	{
		return Text::_($string);
	}

	/**
	 * Replaces the Joomla! root path defined by the constant "JPATH_BASE" with the string "JROOT".
	 *
	 * @param   string  $string  The string to process.
	 *
	 * @return mixed
	 */
	public function stripJRoot($string)
	{
		return str_replace(JPATH_BASE, 'JROOT', $string);
	}
}
