<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View\Renderer;

use Adaptive\Diff\Diff;
use JTracker\DiffRenderer\Html\Inline;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension class
 *
 * @since  1.0
 */
class TrackerExtension extends AbstractExtension
{
	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  TwigFunction[]  An array of functions.
	 *
	 * @since   1.0
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction('contrast_color', [ContrastHelper::class, 'getContrastColor']),
			new TwigFunction('render_diff', [$this, 'renderDiff'], ['is_safe' => ['html'], 'needs_environment' => true]),
			new TwigFunction('string_array_diff', [$this, 'getArrayDiffAsString']),
			new TwigFunction('timezones', [$this, 'getTimezones']),
		];
	}

	/**
	 * Returns a list of filters to add to the existing list.
	 *
	 * @return  TwigFilter[]  An array of filters
	 *
	 * @since   1.0
	 */
	public function getFilters()
	{
		return [
			new TwigFilter('strip_root_path', [$this, 'stripRootPath']),
			new TwigFilter('yesno', [$this, 'yesNo']),
		];
	}

	/**
	 * Get the difference of two comma separated value strings.
	 *
	 * @param   string  $a  The "a" string.
	 * @param   string  $b  The "b" string.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getArrayDiffAsString($a, $b): string
	{
		$as = explode(',', $a);
		$bs = explode(',', $b);

		return implode(',', array_diff($as, $bs));
	}

	/**
	 * Get the timezones.
	 *
	 * @return  array  The timezones.
	 *
	 * @since   1.0
	 */
	public function getTimezones()
	{
		return \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
	}

	/**
	 * Render the differences between two text strings.
	 *
	 * @param   Environment  $twig             The Twig environment.
	 * @param   string       $old              The "old" text.
	 * @param   string       $new              The "new" text.
	 * @param   boolean      $showLineNumbers  To show line numbers.
	 * @param   boolean      $showHeader       To show the table header.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function renderDiff(Environment $twig, $old, $new, $showLineNumbers = true, $showHeader = true)
	{
		$rendererOptions = [
			'show_header'       => (bool) $showHeader,
			'show_line_numbers' => (bool) $showLineNumbers,
		];

		return (new Diff(explode("\n", $old), explode("\n", $new)))
			->render(new Inline($twig, $rendererOptions));
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
	public function stripRootPath($string)
	{
		return str_replace(JPATH_ROOT, 'JROOT', $string);
	}

	/**
	 * Generate a localized yes/no message.
	 *
	 * @param   integer  $value  A value that evaluates to TRUE or FALSE.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function yesNo($value)
	{
		return $value ? 'Yes' : 'No';
	}
}
