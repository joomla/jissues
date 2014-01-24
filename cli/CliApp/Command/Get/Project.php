<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Get;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Project extends Get
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Get the whole project info from GitHub, including issues and issue comments.';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Retrieve Project');

		$this->logOut('Bulk Start retrieve Project');

		$this->selectProject();

		$this->getApplication()->input->set('project', $this->project->project_id);

		$this->setupGitHub()
			->displayGitHubRateLimit()
			->out(
				sprintf(
						'Updating project info for project: %s/%s',
						$this->project->gh_user,
						$this->project->gh_project
					)
				)
			->processLabels()
			->processMilestones()
			->processIssues()
			->processComments()
			->processEvents()
			->processAvatars()
			->out()
			->logOut('Bulk Finished');
	}

	/**
	 * Process the project labels.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processLabels()
	{
		with(new Labels)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project labels.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processMilestones()
	{
		with(new Milestones)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project issues.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processIssues()
	{
		with(new Issues)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project comments.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processComments()
	{
		with(new Comments)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project events.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processEvents()
	{
		with(new Events)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}

	/**
	 * Process the project avatars.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processAvatars()
	{
		with(new Avatars)
			->setContainer($this->getContainer())
			->execute();

		return $this;
	}
}
