<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View\Renderer;

use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension integrating UI asset support
 *
 * @since  1.0
 */
class AssetsExtension extends AbstractExtension
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
			new TwigFunction('asset', [Packages::class, 'getUrl']),
		];
	}
}
