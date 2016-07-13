<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use JTracker\Authentication\Database\TableUsers;

/**
 * Class for updating user information from GitHub.
 *
 * @since  1.0
 */
class Users extends Get
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Retrieve user info from GitHub.');
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

		if ($this->getApplication()->input->get('noprogress'))
		{
			$this->usePBar = false;
		}

		defined('JPATH_THEMES') || define('JPATH_THEMES', JPATH_ROOT . '/www');

		$this->getApplication()->outputTitle(g11n3t('Retrieve Users'));

		$this->logOut(g11n3t('Start retrieving Users.'))
			->setupGitHub()
			->getUserName()
			->out()
			->logOut(g11n3t('Finished.'));
	}

	/**
	 * Fetch Username and store into DB.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	private function getUserName()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		/* @type \Joomla\Github\Github $github */
		$github = $this->getContainer()->get('gitHub');

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
					'Getting user info for one user.',
					'Getting user info for %d users.',
					count($usernames)
				),
				count($usernames)
			)
		);

		$progressBar = $this->getProgressBar(count($usernames));

		$this->usePBar ? $this->out() : null;

		$adds = 0;

		foreach ($usernames as $i => $userName)
		{
			if (!$userName)
			{
				continue;
			}

			$this->debugOut(sprintf(g11n3t('Fetching User Info for user: %s'), $userName));

			try
			{
				$ghUser = $github->users->get($userName);

				$newInfo = new \stdClass;

				// Set name fatched from github
				$newInfo->name = ($ghUser->name != null) ? $ghUser->name : '';

				$table = new TableUsers($db);

				$table->loadByUserName($userName);

				if (!$table->id)
				{
					// Register a new user
					$date               = new Date;
					$newInfo->registerDate = $date->format($db->getDateFormat());

					// Setting null id for new record.
					$newInfo->id = 0;

					// Setting username as we have the only info.
					$newInfo->username = $userName;
				}

				$table->save($newInfo);

				if ($newInfo->name != null)
				{
					$this->debugOut(sprintf(g11n3t('User Name: %s'), $newInfo->name));
				}

				++$adds;

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
			->logOut(
				sprintf(
					g11n4t(
						'Added one new user',
						'Added %d new user',
						$adds
					),
					$adds
				)
			);
	}
}
