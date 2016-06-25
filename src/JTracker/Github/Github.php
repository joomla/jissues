<?php
/**
 * Part of the Joomla Tracker Model Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\GitHub;

use Joomla\Github\Github as JGitHub;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
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
	use LoggerAwareTrait;

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
		$class = 'JTracker\\Github\\Package\\' . ucfirst($name);

		if (class_exists($class))
		{
			if (false === isset($this->$name))
			{
				$this->$name = new $class($this->options, $this->client);

				// Inject the logger
				$this->$name->setLogger($this->getLogger());
			}

			return $this->$name;
		}

		return parent::__get($name);
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
		if (!($this->logger instanceof LoggerInterface))
		{
			$this->logger = new NullLogger;
		}

		return $this->logger;
	}
}
