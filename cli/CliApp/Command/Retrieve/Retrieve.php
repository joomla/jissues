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

use Joomla\Tracker\Components\Tracker\Model\ProjectsModel;

use CliApp\Application\TrackerApplication;
use CliApp\Command\TrackerCommand;
use CliApp\Command\TrackerCommandOption;
use CliApp\Exception\AbortException;

/**
 * Class Retrieve.
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
	 * @var \stdClass
	 */
	protected $project = null;

	/**
	 * Use the progress bar.
	 *
	 * @var  boolean
	 */
	protected $usePBar;

	/**
	 * Progress bar format.
	 *
	 * @var string
	 */
	protected $pBarFormat = '[%bar%] %fraction% %elapsed% ETA: %estimate%';

	/**
	 * Constructor.
	 *
	 * @param   TrackerApplication  $application  The application object.
	 */
	public function __construct(TrackerApplication $application)
	{
		parent::__construct($application);

		$this->description = 'Retrieve <issues> or <comments>.';

		$this->addOption(
			new TrackerCommandOption(
				'project', 'p',
				'Process the project with the given ID.'
			)
		)->addOption(
			new TrackerCommandOption(
				'auth', '',
				'Use GitHub credentials from configuration for authentication.'
			)
		);
	}

	/**
	 * Execute the command.
	 *
	 * @return void
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
	 * @todo this might go to a base class.
	 *
	 * @throws \RuntimeException
	 * @throws AbortException
	 *
	 * @return $this
	 */
	protected function selectProject()
	{
		$projectsModel = new ProjectsModel($this->application->getDatabase());
		$projects      = $projectsModel->getItems();

		$id = $this->application->input->getInt('project', $this->application->input->getInt('p'));

		if (!$id)
		{
			$this->out()
				->out('<b>Available projects:</b>')
				->out();

			foreach ($projects as $i => $project)
			{
				$this->out(($i + 1) . ') ' . $project->title);
			}

			$this->out()
			->out('<question>Select a project:</question> ', false);

			$resp = (int) trim($this->application->in());

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (false == array_key_exists($resp - 1, $projects))
			{
				throw new AbortException('Invalid project');
			}

			$this->project = $projects[$resp - 1];
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

			$this->out('Processing project: <info>' . $this->project->title.'</info>');
		}

		return $this;
	}

	/**
	 * Setup the github object.
	 *
	 * @return $this
	 */
	protected function setupGitHub()
	{
		// Set up JGithub
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
		$transport = \Joomla\Http\HttpFactory::getAvailableDriver($options, array('curl', 'stream'));

		if (is_a($transport, 'Joomla\\Http\\Transport\\Socket'))
		{
			throw new \RuntimeException('Please either enable cURL or url_fopen');
		}

		$http = new \Joomla\Github\Http($options, $transport);

		$this->application->debugOut(get_class($transport));

		// Instantiate JGithub
		$this->github = new Github($options, $http);

		// @todo after fix this should be enough:
		// $this->github = new Github($options);

		return $this;
	}

	/**
	 * Display the GitHub rate limit.
	 *
	 * @return $this
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
	 * @return ConsoleProgressBar
	 */
	protected function getProgressBar($targetNum)
	{
		if(!$this->usePBar)
		{
			return null;
		}

		$bar = '=>';
		$preFill = ' ';
		$width = 60;
		$progressBar = new ConsoleProgressBar($this->pBarFormat, $bar, $preFill, $width, $targetNum);

		return $progressBar;
	}
}
