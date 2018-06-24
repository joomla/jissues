<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use JTracker\Authentication\GitHub\GitHubLoginHelper;

/**
 * Class for retrieving avatars from GitHub for selected projects
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

		$this->description = g11n3t('Retrieve avatar images from GitHub.');
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
		$this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

		if ($this->getOption('noprogress'))
		{
			$this->usePBar = false;
		}

		defined('JPATH_THEMES') || define('JPATH_THEMES', JPATH_ROOT . '/www');

		$this->getApplication()->outputTitle(g11n3t('Retrieve Avatars'));

		$this->logOut(g11n3t('Start retrieving Avatars.'))
			->setupGitHub()
			->fetchAvatars()
			->out()
			->logOut(g11n3t('Finished.'));
	}

	/**
	 * Fetch avatars.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	private function fetchAvatars()
	{
		/** @var \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$usernames = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__activities'))
				->select('DISTINCT ' . $db->quoteName('user'))
				->order($db->quoteName('user'))
		)->loadColumn();

		if (!count($usernames))
		{
			throw new \UnexpectedValueException(g11n3t('No users found in database.'));
		}

		$this->logOut(
			sprintf(
				g11n4t(
					'Processing avatars for one user.',
					'Processing avatars for %d users.',
					count($usernames)
				),
				count($usernames)
			)
		);

		$progressBar = $this->getProgressBar(count($usernames));

		$this->usePBar ? $this->out() : null;

		$base = JPATH_THEMES . '/images/avatars/';
		$adds = 0;

		$loginHelper = new GitHubLoginHelper($this->getContainer());

		foreach ($usernames as $i => $username)
		{
			if (!$username)
			{
				continue;
			}

			if (file_exists($base . '/' . $username . '.png'))
			{
				$this->debugOut(sprintf(g11n3t('User avatar already fetched for user %s'), $username));

				$this->usePBar
					? $progressBar->update($i + 1)
					: $this->out('-', false);

				continue;
			}

			$this->debugOut(sprintf(g11n3t('Fetching avatar for user: %s'), $username));

			try
			{
				$loginHelper->saveAvatar($username);

				++$adds;
			}
			catch (\DomainException $e)
			{
				$this->debugOut($e->getMessage());

				$this->debugOut(sprintf(g11n3t('Copy default image for user: %s'), $username));

				copy(
					JPATH_THEMES . '/images/avatars/user-default.png',
					JPATH_THEMES . '/images/avatars/' . $username . '.png'
				);
			}

			$this->usePBar
				? $progressBar->update($i + 1)
				: $this->out('+', false);
		}

		return $this->out()
			->logOut(
				sprintf(
					g11n4t(
						'Added one new user avatar',
						'Added %d new user avatars',
						$adds
					),
					$adds
				)
			);
	}
}
