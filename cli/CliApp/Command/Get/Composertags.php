<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use CliApp\Command\Make\Depfile;
use CliApp\Command\TrackerCommandOption;

use JTracker\Container;

/**
 * Class for retrieving repository tags from GitHub based on the composer file.
 *
 * @since  1.0
 */
class Composertags extends Get
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Retrieve a list of project tags from GitHub and show their installed versions.';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		$this->application = Container::retrieve('app');
		$this->logger      = Container::retrieve('logger');

		$this
			->addOption(
				new TrackerCommandOption(
					'all', '',
					'Show all tags or only the most recent.'
				)
			);
	}

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Retrieve composer tags');

		$packages = with(new Depfile)->getComposerInstalled();
		$allTags  = $this->application->input->get('all');

		$this->logOut('Start getting composer tags.')
			->setupGitHub()
			->displayGitHubRateLimit()
			->fetchTags($packages, $allTags)
			->out()
			->logOut('Finished.');
	}

	/**
	 * Fetch Tags.
	 *
	 * @param   array    $packages  List of installed packages
	 * @param   boolean  $allTags   Fetch all tags or only the "most recent".
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	private function fetchTags(array $packages, $allTags = false)
	{
		foreach ($packages as $package)
		{
			$this->out($package->name);

			if (!preg_match('|https://github.com/([A-z0-9\-]+)/([A-z0-9\-\.]+).git|', $package->sourceURL, $matches))
			{
				$this->out('CAN NOT PARSE: ' . $package->sourceURL);

				continue;
			}

			$owner = $matches[1];
			$repo  = $matches[2];

			$tags = $this->github->repositories->getListTags($owner, $repo);

			$found = false;

			foreach ($tags as $tag)
			{
				if ($tag->name == $package->version)
				{
					$this->out($tag->name . ' <= Installed');

					$found = true;

					if (!$allTags)
					{
						break;
					}
				}
				else
				{
					$this->out($tag->name);
				}
			}

			if (!$found)
			{
				$this->out('Installed: ' . $package->version);
			}

			$this->out();
		}

		return $this;
	}
}
