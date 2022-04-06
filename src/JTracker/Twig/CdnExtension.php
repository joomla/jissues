<?php
/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig;

use JTracker\Twig\Service\CdnRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension integrating `joomla.org` template elements from the CDN
 *
 * @since  1.0
 */
class CdnExtension extends AbstractExtension
{
	/**
	 * Returns a list of functions to add to the existing list.
	 *
	 * @return  TwigFunction[]  An array of functions.
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction('cdn_footer', [CdnRenderer::class, 'getCdnFooter'], ['is_safe' => ['html']]),
			new TwigFunction('cdn_menu', [CdnRenderer::class, 'getCdnMenu'], ['is_safe' => ['html']]),
		];
	}
}
