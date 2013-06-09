<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Retrieve;

use Elkuku\Console\Helper\ConsoleProgressBar;
use Joomla\Github\Github;
use Joomla\Registry\Registry;

use App\Tracker\Model\ProjectsModel;

use CliApp\Application\TrackerApplication;
use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;
use App\Tracker\Table\ProjectsTable;

/**
 * Class for retrieving data from GitHub for selected projects
 *
 * @since  1.0
 */
class Retrieve extends TrackerCommand
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
	 * @param   TrackerApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		parent::__construct($application);

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
		$this->application->outputTitle('Retrieve');

		$this->out('<error>                                    </error>');
		$this->out('<error>  Please use one of the following:  </error>');
		$this->out('<error>  retrieve comments                 </error>');
		$this->out('<error>  retrieve issues                   </error>');
		$this->out('<error>                                    </error>');
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
		$projects = with(new ProjectsModel($this->application->getDatabase()))->getItems();

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

			$this->out('Processing project: <info>' . $this->project->title . '</info>');
		}

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
		// Set up Github
		$options = new Registry;

		if ($this->application->input->get('auth'))
		{
			$resp = 'yes';
		}
		else
		{
			// Ask if the user wishes to authenticate to GitHub.  Advantage is increased rate limit to the API.
			$this->out('<question>Do you wish to authenticate to GitHub?</question> [y]es / <b>[n]o</b> :', false);

			$resp = trim($this->application->in());
		}

		if ($resp == 'y' || $resp == 'yes')
		{
			// Set the options
			$options->set('api.username', $this->application->get('github.username', ''));
			$options->set('api.password', $this->application->get('github.password', ''));

			$this->application->debugOut('GitHub credentials: ' . print_r($options, true));
		}

		// @todo temporary fix to avoid the "Socket" transport protocol
		$transport = \Joomla\Http\HttpFactory::getAvailableDriver($options, array('curl'));

		if (false == is_a($transport, 'Joomla\\Http\\Transport\\Curl'))
		{
			throw new \RuntimeException('Please enable cURL.');
		}

		$http = new \Joomla\Github\Http($options, $transport);

		$this->application->debugOut(get_class($transport));

		// Instantiate Github
		$this->github = new Github($options, $http);

		// @todo after fix this should be enough:
		// $this->github = new Github($options);

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
		$this->out()
			->out('<info>GitHub rate limit:...</info> ', false);

		$rate = $this->github->authorization->getRateLimit()->rate;

		$this->out(sprintf('%1$d (remaining: <b>%2$d</b>)', $rate->limit, $rate->remaining))
			->out();

		return $this;
	}

	/**
	 * Get a progress bar object.
	 *
	 * @param   integer  $targetNum  The target number.
	 *
	 * @return  ConsoleProgressBar
	 *
	 * @since   1.0
	 */
	protected function getProgressBar($targetNum)
	{
		return ($this->usePBar)
			? new ConsoleProgressBar($this->pBarFormat, '=>', ' ', 60, $targetNum)
			: null;
	}
}
