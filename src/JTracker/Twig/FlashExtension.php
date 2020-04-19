<?php
/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig;

use JTracker\Twig\Service\FlashMessageRetriever;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension integrating support for the application's flash message queue
 */
class FlashExtension extends AbstractExtension
{
	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  TwigFunction[]  An array of functions.
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction('flash_messages', [FlashMessageRetriever::class, 'getMessages']),
		];
	}
}
