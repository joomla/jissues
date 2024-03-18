<?php

/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View\Renderer;

use Symfony\Component\Asset\Context\ContextInterface;
use Symfony\Component\Asset\PathPackage as BasePathPackage;
use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

/**
 * Extended path package checking for assets with unminified versions if the template is in debug mode
 *
 * @since  1.0
 */
class DebugPathPackage extends BasePathPackage
{
    /**
     * Flag if the template is in debug mode
     *
     * @var    boolean
     * @since  1.0
     */
    private $debug;

    /**
     * Package constructor
     *
     * @param   string                    $basePath         The base path assets are found in relative to the web directory
     * @param   VersionStrategyInterface  $versionStrategy  The version strategy to use with this package
     * @param   ContextInterface          $context          The context to use with this package
     * @param   boolean                   $debug            Flag if the template is in debug mode
     *
     * @since   1.0
     */
    public function __construct($basePath, VersionStrategyInterface $versionStrategy, ContextInterface $context, $debug)
    {
        parent::__construct($basePath, $versionStrategy, $context);

        $this->debug = $debug;
    }

    /**
     * Returns an absolute or root-relative public path.
     *
     * @param   string  $path  A path
     *
     * @return  string  The public path
     *
     * @since   1.0
     */
    public function getUrl($path): string
    {
        if ($this->isAbsoluteUrl($path)) {
            return $path;
        }

        // Check if the path is looking for a minified file in debug mode and return an uncompressed file if available
        if ($this->debug && strpos($path, '.min') !== false) {
            $altPath = str_replace('.min', '', $path);

            if (file_exists(JPATH_THEMES . $this->getBasePath() . ltrim($altPath, '/'))) {
                $path = $altPath;
            }
        }

        return $this->getBasePath() . ltrim($this->debug ? $path : $this->getVersionStrategy()->applyVersion($path), '/');
    }
}
