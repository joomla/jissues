<?php

/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\Github;

use Joomla\Github\Github as JGitHub;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Joomla! Tracker class for interacting with a GitHub server instance.
 *
 * @property-read  Package\Issues         $issues         GitHub API object for the issues package.
 * @property-read  Package\Repositories   $repositories   GitHub API object for the repositories package.
 * @property-read  Package\Markdown       $markdown       GitHub API object for the issues package.
 *
 * @since  1.0
 */
class Github extends JGitHub implements LoggerAwareInterface
{
    /**
     * Logger
     *
     * @var    LoggerInterface
     * @since  1.0
     */
    protected $logger;

    /**
     * The API objects created so far, keyed by name.
     *
     * They are kept here instead of in dynamic properties, which are deprecated since PHP 8.2.
     *
     * @var    object[]
     * @since  1.0
     */
    private $packages = [];

    /**
     * Magic method to lazily create API objects
     *
     * @param   string  $name  Name of property to retrieve
     *
     * @return  GithubObject  GitHub API object (gists, issues, pulls, etc).
     *
     * @since   1.0
     * @throws  \InvalidArgumentException If $name is not a valid sub class.
     */
    public function __get($name)
    {
        if (isset($this->packages[$name])) {
            return $this->packages[$name];
        }

        // Use the tracker's own package if there is one, the package from the GitHub library otherwise.
        $class = 'JTracker\\Github\\Package\\' . ucfirst($name);

        if (class_exists($class)) {
            $package = new $class($this->options, $this->client);

            // Inject the logger
            $package->setLogger($this->getLogger());
        } else {
            $class = 'Joomla\\Github\\Package\\' . ucfirst($name);

            if (!class_exists($class)) {
                throw new \InvalidArgumentException(\sprintf('Argument %s produced an invalid class name: %s', $name, $class));
            }

            $package = new $class($this->options, $this->client);
        }

        return $this->packages[$name] = $package;
    }

    /**
     * Get the logger.
     *
     * @return  LoggerInterface
     *
     * @since   1.0
     */
    public function getLogger()
    {
        // If a logger hasn't been set, use NullLogger
        if (!($this->logger instanceof LoggerInterface)) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * Sets a logger.
     *
     * @param   LoggerInterface  $logger  The logger object.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
