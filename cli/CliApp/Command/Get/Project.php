<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use Joomla\DI\Container;

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
	 * @param   Container  $container  The DI container.
	 *
	 * @since   1.0
	 */
	public function __construct(Container $container)
	{
		parent::__construct($container);

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
		with(new Labels($this->container))
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
		with(new Issues($this->container))
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
		with(new Comments($this->container))
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
		with(new Avatars($this->container))
			->execute();

		return $this;
	}
}
