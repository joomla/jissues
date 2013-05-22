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
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  array  An array of functions.
	 */
	public function getFunctions()
	{
		return array(
			new \Twig_SimpleFunction('translate', array($this, 'translate')),
			new \Twig_SimpleFunction('sprintf', 'sprintf'),
			new \Twig_SimpleFunction('stripJRoot', array($this, 'stripJRoot')),
			new \Twig_SimpleFunction('avatar', array($this, 'fetchAvatar')),
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

	/**
	 * Fetch an avatar.
	 *
	 * @param   string   $userName  The user name.
	 * @param   integer  $width     The with in pixel.
	 *
	 * @return string
	 */
	public function fetchAvatar($userName = '', $width = 0)
	{
		static $avatars = array();

		if (array_key_exists($userName, $avatars))
		{
			$avatar = $avatars[$userName];
		}
		else
		{
			if (!$userName)
			{
				$avatar = 'user-default.png';
			}
			else
			{
				/* @type \Joomla\Database\DatabaseDriver $db */
				$db = Factory::$application->getDatabase();

				$avatar = $db->setQuery(
					$db->getQuery(true)
						->from($db->quoteName('#__users'))
						->select($db->quoteName('avatar'))
						->where($db->quoteName('username') . ' = ' . $db->quote($userName))
				)->loadResult();

				$avatar = $avatar ? : 'user-default.png';
			}

			$avatars[$userName] = $avatar;
		}

		$width = $width ? ' width="' . $width . 'px"' : '';

		return '<img'
		. ' alt="avatar ' . $userName . '"'
		. ' src="/images/avatars/' . $avatar . '"'
		. $width
		. ' />';
	}
}
