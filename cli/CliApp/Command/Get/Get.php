<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Projects\Table\ProjectsTable;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;

use Joomla\Filesystem\Folder;

use JTracker\Github\Github;
use JTracker\Container;

/**
 * Class for retrieving data from GitHub for selected projects
 *
 * @since  1.0
 */
class Get extends TrackerCommand
{
	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * The id of the current bot.
	 *
	 * @var    integer
	 * @since  1.0
	 */
	protected $botId = 0;

	/**
	 * Project object.
	 *
	 * @var    ProjectsTable
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Retrieve <cmd><project></cmd>, <cmd><avatars></cmd> or <cmd><composertags></cmd>.';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this
			->addOption(
				new TrackerCommandOption(
					'project', 'p',
					'Process the project with the given ID.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'noprogress', '',
					'Don\'t use a progress bar.'
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
		$this->application->outputTitle('Get');

		$errorTitle = 'Please use one of the following:';

		$this->out('<error>                                    </error>');
		$this->out('<error>  ' . $errorTitle . '  </error>');

		foreach (Folder::files(__DIR__) as $file)
		{
			$cmd = strtolower(substr($file, 0, strlen($file) - 4));

			if ('get' == $cmd)
			{
				continue;
			}

			$this->out('<error>  get ' . $cmd . str_repeat(' ', strlen($errorTitle) - strlen($cmd) - 3) . '</error>');
		}

		$this->out('<error>                                    </error>');
	}

	/**
	 * Setup the Github object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupGitHub()
	{
		$this->github = Container::retrieve('gitHub');

		return $this;
	}

	/**
	 * Check the remaining GitHub rate limit.
	 *
	 * @param   integer  $remaining  The remaining count.
	 *
	 * @throws \RuntimeException
	 * @return $this
	 *
	 * @since   1.0
	 */
	protected function checkGitHubRateLimit($remaining)
	{
		// @todo hard coded values..
		$minSwitch = 500;
		$minRemain = 10;

		$this->debugOut(sprintf('Limit check: %1$d -- %2$d / %3$d', $remaining, $minSwitch, $minRemain));

		if ($remaining <= $minSwitch)
		{
			$this->switchGitHubAccount();
		}

		if ($remaining <= $minRemain)
		{
			throw new \RuntimeException(
				sprintf(
					'GitHub remaining rate limit (%1$d) dropped below the minimum (%2$d) for user %3$s.',
					$remaining, $minRemain, $this->github->getOption('api.username')
				)
			);
		}

		return $this;
	}

	/**
	 * Cycle through a list of GitHub accounts for "long running processes".
	 *
	 * @return $this
	 *
	 * @since   1.0
	 * @throws \UnexpectedValueException
	 */
	public function switchGitHubAccount()
	{
		$accounts = $this->github->getOption('api.accounts');

		if (!$accounts)
		{
			return $this;

			// @todo throw new \UnexpectedValueException('No GitHub accounts set in config.');
		}

		// Increase or reset the bot id counter.
		$this->botId = ($this->botId + 1 >= count($accounts)) ? 0 : $this->botId + 1;

		$username = $accounts[$this->botId]->username;
		$password = $accounts[$this->botId]->password;

		$this->github->setOption('api.username', $username);
		$this->github->setOption('api.password', $password);

		$this->logOut(sprintf('Switched to bot account %s (%d)', $username, $this->botId));

		return $this;
	}
}
