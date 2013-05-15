<?php
/**
 * @package    JTracker\View\Renderer
 *
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
			'www' => JPATH_THEMES,
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
		);
	}

	public function translate($string)
	{
		return Text::_($string);
	}
}
