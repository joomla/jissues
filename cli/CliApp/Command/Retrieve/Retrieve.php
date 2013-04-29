<?php
/**
 * @package     JTracker
 * @subpackage  CLI
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Retrieve;

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
		$this->out('Please select either "comments" or "issues"');
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
			foreach ($projects as $i => $project)
			{
				$this->out(($i + 1) . ') ' . $project->title);
			}

			$this->out('Select a project: ', false);

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

			$this->out('Processing project: ' . $this->project->title);
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
			$this->out('Do you wish to authenticate to GitHub? [y]es / [n]o :', false);

			$resp = trim($this->application->in());
		}

		if ($resp == 'y' || $resp == 'yes')
		{
			// Set the options
			$options->set('api.username', $this->application->get('github_user', ''));
			$options->set('api.password', $this->application->get('github_password', ''));

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

		// @todo after fix:
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
			->out('GitHub rate limit:... ', false);

		$rate = $this->github->authorization->getRateLimit()->rate;

		$this->out(sprintf('%1$d (remaining: %2$d)', $rate->limit, $rate->remaining))
			->out();

		return $this;
	}
}
