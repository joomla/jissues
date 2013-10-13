<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Update;

/**
 * Class for updating pull requests GitHub for selected projects
 *
 * @since  1.0
 */
class Pulls extends Update
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Updates selected information for pull requests on GitHub for a specified project.';
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
		$this->application->outputTitle('Update Pull Requests');

		$this->logOut('Start Updating Project');

		$this->selectProject();

		$this->application->input->set('project', $this->project->project_id);

		$this->setupGitHub()
			->displayGitHubRateLimit()
			->out(
				sprintf(
						'Updating pull requests for project: %s/%s',
						$this->project->gh_user,
						$this->project->gh_project
					)
				)
			->tagPulls()
			->out()
			->logOut('Finished');
	}

	/**
	 * Tag pull requests
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	protected function tagPulls()
	{
		// Only process for joomla/joomla-cms
		if ($this->project->gh_user == 'joomla' && $this->project->gh_project == 'joomla-cms')
		{
			$this->out(sprintf('Retrieving <b>open</b> pull requests from GitHub...'), false);
			$this->debugOut('For: ' . $this->project->gh_user . '/' . $this->project->gh_project);

			$pulls = array();
			$page  = 0;

			do
			{
				$page++;
				$pulls_more = $this->github->pulls->getList(
					// Owner
					$this->project->gh_user,
					// Repository
					$this->project->gh_project,
					// State
					'open',
					// Page
					$page,
					// Count
					100
				);

				$count = is_array($pulls_more) ? count($pulls_more) : 0;

				if ($count)
				{
					$pulls = array_merge($pulls, $pulls_more);

					$this->out('(' . $count . ')', false);
				}
			}
			while ($count);

			foreach ($pulls as $pull)
			{
				// Extract some data
				$pullID     = $pull->number;
				$target     = $pull->base->ref;
				$issueLabel = 'PR-' . $target;
				$labelSet   = false;

				// Get the labels for the pull's issue
				$labels = $this->github->issues->get($this->project->gh_user, $this->project->gh_project, $pullID)->labels;

				// Check if the PR- label present
				foreach ($labels as $label);
				{
					if ($label->name == $issueLabel)
					{
						$this->out('PR joomla/joomla-cms#' . $pullID . ' already has branch label, skipping.');
						$labelSet = true;
						continue;
					}
				}

				// Add the label if we need to
				if (!$labelSet)
				{
					// Get the current labels so we don't lose them
					$currentLabels = array();

					foreach ($labels as $label);
					{
						$currentLabels[] = $label->name;
					}

					// Add the issue label
					$currentLabels[] = $issueLabel;

					// Post the new label on the object
					$this->out('Adding branch label to joomla/joomla-cms#' . $pullID);

					$this->github->issues->edit(
						$this->project->gh_user, $this->project->gh_project, $pullID, null, null, null, null, null, $currentLabels
					);
				}
			}
		}

		return $this;
	}
}
