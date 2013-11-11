<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\View\Renderer;

use g11n\g11n;

use JTracker\Container;

/**
 * Twig extension class
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
		/* @var \JTracker\Application $app */
		$app = Container::retrieve('app');

		return array(
			'uri'    => $app->get('uri'),
			'jdebug' => JDEBUG,
			'lang'   => g11n::getCurrent(),
			'languages' => $app->get('languages')
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
		$functions = array(
			new \Twig_SimpleFunction('translate', 'g11n3t'),
			new \Twig_SimpleFunction('g11n4t', 'g11n4t'),
			new \Twig_SimpleFunction('sprintf', 'sprintf'),
			new \Twig_SimpleFunction('stripJRoot', array($this, 'stripJRoot')),
			new \Twig_SimpleFunction('avatar', array($this, 'fetchAvatar')),
			new \Twig_SimpleFunction('prioClass', array($this, 'getPrioClass')),
			new \Twig_SimpleFunction('statuses', array($this, 'getStatus')),
			new \Twig_SimpleFunction('issueLink', array($this, 'issueLink')),
			new \Twig_SimpleFunction('getRelTypes', array($this, 'getRelTypes')),
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
	 * @return  array  An array of filters
	 *
	 * @since   1.0
	 */
	public function getFilters()
	{
		return array(
			new \Twig_SimpleFilter('basename', 'basename'),
			new \Twig_SimpleFilter('get_class', 'get_class'),
			new \Twig_SimpleFilter('json_decode', 'json_decode'),
			new \Twig_SimpleFilter('stripJRoot', array($this, 'stripJRoot')),
			new \Twig_SimpleFilter('contrastColor', array($this, 'getContrastColor')),
			new \Twig_SimpleFilter('labels', array($this, 'renderLabels')),
			new \Twig_SimpleFilter('yesno', array($this, 'yesNo')),
			new \Twig_SimpleFilter('_', 'g11n3t'),
		);
	}

	/**
	 * Replaces the Joomla! root path defined by the constant "JPATH_ROOT" with the string "JROOT".
	 *
	 * @param   string  $string  The string to process.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public function stripJRoot($string)
	{
		return str_replace(JPATH_ROOT, 'JROOT', $string);
	}

	/**
	 * Fetch an avatar.
	 *
	 * @param   string   $userName  The user name.
	 * @param   integer  $width     The with in pixel.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function fetchAvatar($userName = '', $width = 0)
	{
		/* @type \JTracker\Application $app */
		$app = Container::retrieve('app');

		$base = $app->get('uri.base.path');

		$avatar = $userName ? $userName . '.png' : 'user-default.png';

		$width = $width ? ' style="width: ' . $width . 'px"' : '';

		return '<img'
		. ' class="avatar"'
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
	 *
	 * @since   1.0
	 */
	public function getPrioClass($priority)
	{
		switch ($priority)
		{
			case 1 :
				return 'badge-important';

			case 2 :
				return 'badge-warning';

			case 3 :
				return 'badge-info';

			case 4 :
				return 'badge-inverse';

			default :
				return '';
		}
	}

	/**
	 * Dummy function to prevent throwing exception on dump function in the non-debug mode.
	 *
	 * @return  void
	 *
	 * @since   1.0
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
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function getStatus($id)
	{
		static $statuses = array();

		if (!$statuses)
		{
			$db = Container::retrieve('db');

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

	/**
	 * Get a contrasting color (black or white).
	 *
	 * http://24ways.org/2010/calculating-color-contrast/
	 *
	 * @param   string  $hexColor  The hex color.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getContrastColor($hexColor)
	{
		$r = hexdec(substr($hexColor, 0, 2));
		$g = hexdec(substr($hexColor, 2, 2));
		$b = hexdec(substr($hexColor, 4, 2));
		$yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

		return ($yiq >= 128) ? 'black' : 'white';
	}

	/**
	 * Render a list of labels.
	 *
	 * @param   string  $idsString  Comma separated list of IDs.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderLabels($idsString)
	{
		static $labels;

		if (!$labels)
		{
			/* @type \JTracker\Application $application */
			$application = Container::retrieve('app');

			$labels = $application->getProject()->getLabels();
		}

		$html = array();

		$ids = ($idsString) ? explode(',', $idsString) : array();

		foreach ($ids as $id)
		{
			if (array_key_exists($id, $labels))
			{
				$bgColor = $labels[$id]->color;
				$color   = $this->getContrastColor($bgColor);
			}
			else
			{
				$bgColor = '000000';
				$color   = 'ffffff';
			}

			$html[] = '<span class="label"' . ' style="background-color: #' . $bgColor . '; color: ' . $color . ';">';
			$html[] = $labels[$id]->name;
			$html[] = '</span>';
		}

		return implode("\n", $html);
	}

	/**
	 * Get HTML for an issue link.
	 *
	 * @param   integer  $number  Issue number.
	 * @param   boolean  $closed  Issue closed status.
	 * @param   string   $title   Issue title.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function issueLink($number, $closed, $title = '')
	{
		/* @type \JTracker\Application $application */
		$application = Container::retrieve('app');

		$html = array();

		$title = ($title) ? : ' #' . $number;
		$href = $application->get('uri')->base->path . 'tracker/' . $application->getProject()->alias . '/' . $number;

		$html[] = '<a href="' . $href . '"' . ' title="' . $title . '"' . '>';
		$html[] = $closed ? '<del># ' . $number . '</del>' : '# ' . $number;
		$html[] = '</a>';

		return implode("\n", $html);
	}

	/**
	 * Get relation types.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getRelTypes()
	{
		static $relTypes = array();

		if (!$relTypes)
		{
			$db = Container::retrieve('db');

			$relTypes = $db->setQuery(
				$db->getQuery(true)
					->from($db->quoteName('#__issues_relations_types'))
					->select($db->quoteName('id', 'value'))
					->select($db->quoteName('name', 'text'))
			)->loadObjectList();
		}

		return $relTypes;
	}

	public function yesNo($value)
	{
		return $value ? g11n3t('Yes') : g11n3t('No');
	}
}
