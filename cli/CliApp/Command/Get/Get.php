<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Projects\Model\ProjectsModel;
use App\Projects\Table\ProjectsTable;

use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;

use Joomla\Github\Github;

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
	 * @var    ProjectsTable
	 * @since  1.0
	 */
	protected $project = null;

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Progress bar format.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $pBarFormat = '[%bar%] %fraction% %elapsed% ETA: %estimate%';

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Retrieve <cmd><issues></cmd>, <cmd><comments></cmd> or <cmd><avatars></cmd>.';

		$this
			->addOption(
				new TrackerCommandOption(
					'project', 'p',
					'Process the project with the given ID.'
				)
			)
			->addOption(
				new TrackerCommandOption(
					'auth', '',
					'Use GitHub credentials from configuration for authentication.'
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

		$this
			->out('<error>                                    </error>')
			->out('<error>  Please use one of the following:  </error>')
			->out('<error>                                    </error>')
			->out('<error>  get project                       </error>')
			->out('<error>  get issues                        </error>')
			->out('<error>  get comments                      </error>')
			->out('<error>  get avatars                       </error>')
			->out('<error>                                    </error>');
	}

	/**
	 * Select the project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 * @todo    this might go to a base class.
	 */
	protected function selectProject()
	{
		$projects = with(new ProjectsModel(Container::getInstance()->get('db')))->getItems();

		$id = $this->application->input->getInt('project', $this->application->input->getInt('p'));

		if (!$id)
		{
			$this->out()
				->out('<b>Available projects:</b>')
				->out();

			$cnt = 1;

			$checks = array();

			foreach ($projects as $project)
			{
				if ($project->gh_user && $project->gh_project)
				{
					$this->out('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
					$checks[$cnt] = $project;
					$cnt++;
				}
			}

			$this->out()
				->out('<question>Select a project:</question> ', false);

			$resp = (int) trim($this->application->in());

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (false == array_key_exists($resp, $checks))
			{
				throw new AbortException('Invalid project');
			}

			$this->project = $checks[$resp];
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->project_id == $id)
				{
					$this->project = $project;

					break;
				}
			}

			if (is_null($this->project))
			{
				throw new AbortException('Invalid project');
			}
		}

		$this->logOut('Processing project: <info>' . $this->project->title . '</info>');

		return $this;
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
	 * Display the GitHub rate limit.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function displayGitHubRateLimit()
	{
		$this->application->displayGitHubRateLimit();

		return $this;
	}

	/**
	 * Get a progress bar object.
	 *
	 * @param   integer  $targetNum  The target number.
	 *
	 * @return  \Elkuku\Console\Helper\ConsoleProgressBar
	 *
	 * @since   1.0
	 */
	protected function getProgressBar($targetNum)
	{
		return $this->application->getProgressBar($targetNum);
	}
}
