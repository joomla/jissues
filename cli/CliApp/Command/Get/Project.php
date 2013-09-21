<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
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
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Get the whole project info from GitHub, including issues and issue comments.';
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
		$this->application->outputTitle('Retrieve Project');

		$this->logOut('Bulk Start retrieve Project');

		$this->selectProject();

		$this->application->input->set('project', $this->project->project_id);

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
			->processIssues()
			->processComments()
			->processAvatars()
			->out()
			->logOut('Bulk Finished');
	}

	/**
	 * Process the project labels.
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	protected function processLabels()
	{
		with(new Labels)
			->execute();

		return $this;
	}

	/**
	 * Process the project issues.
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	protected function processIssues()
	{
		with(new Issues)
			->execute();

		return $this;
	}

	/**
	 * Process the project comments.
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	protected function processComments()
	{
		with(new Comments)
			->execute();

		return $this;
	}

	/**
	 * Process the project avatars.
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	protected function processAvatars()
	{
		with(new Avatars)
			->execute();

		return $this;
	}
}
