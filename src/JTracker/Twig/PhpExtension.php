<?php

/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension integrating miscellaneous PHP functions into Twig
 *
 * @since  1.0
 */
class PhpExtension extends AbstractExtension
{
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
            new TwigFilter('basename', 'basename'),
            new TwigFilter('get_class', 'get_class'),
            new TwigFilter('json_decode', 'json_decode'),
        ];
    }

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
            new TwigFunction('sprintf', 'sprintf'),
        ];
    }
}
