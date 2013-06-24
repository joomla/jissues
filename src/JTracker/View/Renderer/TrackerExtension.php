<?php
/**
 * @copyright  Copyright (C) 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\View\Renderer;

use g11n\g11n;

use Joomla\Factory;

use JTracker\Application\TrackerApplication;

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
			'lang'   => g11n::getDefault(),
			'languages' => $app->get('languages')
		);
	}

	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  array  An array of functions.
	 */
	public function getFunctions()
	{
		$functions = array(
			new \Twig_SimpleFunction('translate', 'g11n3t'),
			new \Twig_SimpleFunction('sprintf', 'sprintf'),
			new \Twig_SimpleFunction('stripJRoot', array($this, 'stripJRoot')),
			new \Twig_SimpleFunction('avatar', array($this, 'fetchAvatar')),
			new \Twig_SimpleFunction('prioClass', array($this, 'getPrioClass')),
			new \Twig_SimpleFunction('statuses', array($this, 'getStatus')),
		);

		if (!JDEBUG)
		{
			array_push($functions, new \Twig_SimpleFunction('dump', array($this, 'dump')));
		}

		return $functions;
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return  array An array of filters
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('basename', 'basename'),
			new \Twig_SimpleFilter('get_class', 'get_class'),
			new \Twig_SimpleFilter('json_decode', 'json_decode'),
			new \Twig_SimpleFilter('stripJRoot', array($this, 'stripJRoot')),
			new \Twig_SimpleFilter('_', 'g11n3t'),
		);
	}

	/**
	 * Replaces the Joomla! root path defined by the constant "JPATH_BASE" with the string "JROOT".
	 *
	 * @param   string  $string  The string to process.
	 *
	 * @return  mixed
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
	 * @return  string
	 */
	public function fetchAvatar($userName = '', $width = 0)
	{
		/* @var TrackerApplication $app */
		$app = Factory::$application;

		$base = $app->get('uri.base.path');

		$avatar = $userName ? $userName . '.png' : 'user-default.png';

		$width = $width ? ' width="' . $width . 'px"' : '';

		return '<img'
		. ' alt="avatar ' . $userName . '"'
		. ' src="' . $base . 'images/avatars/' . $avatar . '"'
		. $width
		. ' />';
	}

	/**
	 * Get a CSS class according to the item priority.
	 *
	 * @param   integer  $priority  The priority
	 *
	 * @return  string
	 */
	public function getPrioClass($priority)
	{
		$class = '';

		switch ($priority)
		{
			case 1 :
				$class = 'badge-important';
				break;

			case 2 :
				$class = 'badge-warning';
				break;

			case 3 :
				$class = 'badge-info';
				break;

			case 4 :
				$class = 'badge-inverse';
				break;
		}

		return $class;
	}

	/**
	 * Dummy function to prevent throwing exception on dump function in the non-debug mode.
	 *
	 * @return  void
	 */
	public function dump()
	{
		return;
	}

	/**
	 * Get a status object based on its id.
	 *
	 * @param   integer  $id  The id
	 *
	 * @throws \UnexpectedValueException
	 * @return object
	 */
	public function getStatus($id)
	{
		static $statuses = array();

		if (!$statuses)
		{
			/* @type \JTracker\Application\TrackerApplication $application */
			$application = Factory::$application;

			$db = $application->getDatabase();

			$items = $db->setQuery(
				$db->getQuery(true)
					->from($db->quoteName('#__status'))
					->select('*')
			)->loadObjectList();

			foreach ($items as $status)
			{
				$status->cssClass = $status->closed ? 'error' : 'success';
				$statuses[$status->id] = $status;
			}
		}

		if (!array_key_exists($id, $statuses))
		{
			throw new \UnexpectedValueException('Unknown status id:' . (int) $id);
		}

		return $statuses[$id];
	}
}
