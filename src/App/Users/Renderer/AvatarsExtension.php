<?php

/**
 * Part of the Joomla Tracker's Users Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Users\Renderer;

use Joomla\Application\AbstractApplication;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension integrating avatar support
 *
 * @since  1.0
 */
class AvatarsExtension extends AbstractExtension
{
    /**
     * The active application
     *
     * @var    AbstractApplication
     * @since  1.0
     */
    private $app;

    /**
     * Constructor.
     *
     * @param   AbstractApplication  $app  The active application.
     *
     * @since   1.0
     */
    public function __construct(AbstractApplication $app)
    {
        $this->app = $app;
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
            new TwigFunction('avatar', [$this, 'fetchAvatar'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Fetch an avatar.
     *
     * @param   string   $userName  The user name.
     * @param   integer  $width     The with in pixel.
     * @param   string   $class     The class.
     *
     * @return  string
     *
     * @since   1.0
     * @todo    Refactor avatar paths to use the media directory
     */
    public function fetchAvatar($userName = '', $width = 0, $class = '')
    {
        $base = $this->app->get('uri.base.path');

        $avatar = $userName ? $userName . '.png' : 'user-default.png';

        $width = $width ? ' style="width: ' . $width . 'px"' : '';
        $class = $class ? ' class="' . $class . '"' : '';

        return '<img'
        . $class
        . ' alt="avatar ' . $userName . '"'
        . ' src="' . $base . 'images/avatars/' . $avatar . '"'
        . $width
        . ' />';
    }
}
