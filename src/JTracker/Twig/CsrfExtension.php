<?php

/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig;

use JTracker\Security\CsrfTokenManager;
use JTracker\Twig\Service\CsrfFieldRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension exposing the CSRF token to templates
 *
 * @since  __DEPLOY_VERSION__
 */
class CsrfExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return  TwigFunction[]  An array of functions.
     *
     * @since   __DEPLOY_VERSION__
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('csrf_token', [CsrfTokenManager::class, 'getToken']),
            new TwigFunction('csrf_field', [CsrfFieldRenderer::class, 'render'], ['is_safe' => ['html']]),
        ];
    }
}
