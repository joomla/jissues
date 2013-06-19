<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use CliApp\Application\TrackerApplication;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Authentication\GitHub\GitHubUser;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Avatars extends Get
{
	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;
		$this->description = 'Retrieve avatar images from GitHub.';
		$this->usePBar     = $this->application->get('cli-application.progress-bar');

		if ($this->application->input->get('noprogress'))
		{
			$this->usePBar = false;
		}

		defined('JPATH_THEMES') || define('JPATH_THEMES', JPATH_ROOT . '/www');
		defined('JPATH_SITE') || define('JPATH_SITE', JPATH_ROOT);
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
		$this->application->outputTitle('Retrieve Issues');

		$this
			->setupGitHub()
			->displayGitHubRateLimit();

		$db = $this->application->getDatabase();

		$users = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__activities'))
				->select('DISTINCT ' . $db->quoteName('user'))
				->order($db->quoteName('user'))
		)->loadColumn();

		$this->out(sprintf("Found %d users in the database", count($users)));

		$g = new GitHubUser;

		$progressBar = $this->getProgressBar(count($users));

		$this->usePBar ? $this->out() : null;

		foreach ($users as $i => $user)
		{
			if (!$user)
			{
				$this->usePBar
					? $progressBar->update($i + 1)
					: null;

				continue;
			}

			$g->username = $user;

			if (false === strpos(GitHubLoginHelper::getAvatarPath($g), 'user-default.png'))
			{
				$this->usePBar
					? $progressBar->update($i + 1)
					: $this->out('-', false);

				$this->debugOut('User already fetched: ' . $user);

				continue;
			}

			$this->usePBar
				? $progressBar->update($i + 1)
				: $this->out('+', false);

			$this->debugOut('Fetching avatar for user: ' . $user);

			$g->avatar_url = $this->github->users->get($user)->avatar_url;

			GitHubLoginHelper::saveAvatar($g);
		}

		$this->out()
			->out('Finished =;)');
	}
}
