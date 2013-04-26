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

use CliApp\Exception\AbortException;
use CliApp\Command\TrackerCommand;

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

		$id = $this->input->getInt('project', $this->input->getInt('p'));

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

		if ($this->input->get('auth'))
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
		}

		// Instantiate JGithub
		$this->github = new Github($options);

		return $this;
	}
}
