<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use JTracker\Authentication\GitHub\GitHubLoginHelper;
use JTracker\Container;

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
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Retrieve avatar images from GitHub.';
		$this->usePBar     = $this->application->get('cli-application.progress-bar');

		if ($this->application->input->get('noprogress'))
		{
			$this->usePBar = false;
		}

		defined('JPATH_THEMES') || define('JPATH_THEMES', JPATH_ROOT . '/www');
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
		$this->application->outputTitle('Retrieve Avatars');

		$this->logOut('Start retrieve Avatars.')
			->setupGitHub()
			->displayGitHubRateLimit()
			->fetchAvatars()
			->out()
			->logOut('Finished.');
	}

	/**
	 * Fetch avatars.
	 *
	 * @return $this
	 *
	 * @throws \UnexpectedValueException
	 * @since  1.0
	 */
	private function fetchAvatars()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = Container::getInstance()->get('db');

		$usernames = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__activities'))
				->select('DISTINCT ' . $db->quoteName('user'))
				->order($db->quoteName('user'))
		)->loadColumn();

		if (!count($usernames))
		{
			throw new \UnexpectedValueException('No users found in database.');
		}

		$this->logOut(sprintf('Processing avatars for %d users.', count($usernames)));

		$progressBar = $this->getProgressBar(count($usernames));

		$this->usePBar ? $this->out() : null;

		$base = JPATH_THEMES . '/images/avatars/';
		$adds = 0;

		foreach ($usernames as $i => $username)
		{
			if (!$username)
			{
				continue;
			}

			if (file_exists($base . '/' . $username . '.png'))
			{
				$this->debugOut('User avatar already fetched: ' . $username);

				$this->usePBar
					? $progressBar->update($i + 1)
					: $this->out('-', false);

				continue;
			}

			$this->debugOut('Fetching avatar for user: ' . $username);

			try
			{
				GitHubLoginHelper::saveAvatar($username);

				++ $adds;
			}
			catch (\DomainException $e)
			{
				$this->debugOut($e->getMessage());
			}

			$this->usePBar
				? $progressBar->update($i + 1)
				: $this->out('+', false);
		}

		return $this->out()
			->logOut(sprintf('Added %d new user avatars', $adds));
	}
}
