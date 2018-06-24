<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Update;

/**
 * Class for updating pull requests GitHub for selected projects
 *
 * @since  1.0
 */
class Pulls extends Update
{
	/**
	 * Array containing the pull requests being processed
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $pulls = [];

	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Updates selected information for pull requests on GitHub for a specified project.');
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
		$this->getApplication()->outputTitle(g11n3t('Update Pull Requests'));

		$this->logOut(g11n3t('Start Updating Project'));

		$this->selectProject();

		$this->getApplication()->input->set('project', $this->project->project_id);

		$this->setupGitHub()
			->displayGitHubRateLimit()
			->out(
				sprintf(
					g11n3t('Updating pull requests for project: %s/%s'),
					$this->project->gh_user,
					$this->project->gh_project
				)
			)
			->fetchPulls()
			->labelPulls()
			->updatePullStatus()
			->closePulls()
			->out()
			->logOut(g11n3t('Finished.'));
	}

	/**
	 * Closes pull requests meeting specific criteria
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function closePulls()
	{
		// Only process for joomla/joomla-cms
		if ($this->project->gh_user == 'joomla' && $this->project->gh_project == 'joomla-cms')
		{
			$message = 'Joomla! 2.5 is no longer supported.  Pull requests for this branch are no longer accepted.';

			foreach ($this->pulls as $pull)
			{
				if ($pull->base->ref == '2.5.x')
				{
					// We have to do this in two requests; first add our closing comment then close the item
					$this->github->issues->comments->create(
						$this->project->gh_user, $this->project->gh_project, $pull->number, $message
					);

					$this->github->pulls->edit(
						$this->project->gh_user, $this->project->gh_project, $pull->number, null, null, 'closed'
					);

					$this->out(
						sprintf(
							g11n3t('GitHub item %s/%s #%d has been closed because it is a pull targeting Joomla! 2.5.'),
							$this->project->gh_user,
							$this->project->gh_project,
							$pull->number
						)
					);
				}
			}
		}
		else
		{
			$this->out(
				sprintf(
					g11n3t('The %s/%s project is not supported by this command at this time.'),
					$this->project->gh_user,
					$this->project->gh_project
				)
			);
		}

		return $this;
	}

	/**
	 * Retrieves pull requests
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function fetchPulls()
	{
		$this->out(sprintf('Retrieving <b>open</b> pull requests from GitHub...'), false);
		$this->debugOut('For: ' . $this->project->gh_user . '/' . $this->project->gh_project);

		$pulls = [];
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

		$this->pulls = $pulls;

		return $this;
	}

	/**
	 * Label pull requests
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function labelPulls()
	{
		// Only process for joomla/joomla-cms
		if ($this->project->gh_user == 'joomla' && $this->project->gh_project == 'joomla-cms')
		{
			foreach ($this->pulls as $pull)
			{
				// Extract some data
				$pullID     = $pull->number;
				$issueLabel = 'PR-' . $pull->base->ref;
				$labelSet   = false;

				// Get the labels for the pull's issue
				$labels = $this->github->issues->labels->getListByIssue($this->project->gh_user, $this->project->gh_project, $pullID);

				// Check if the PR- label present
				foreach ($labels as $label)
				{
					if ($label->name == $issueLabel)
					{
						$this->out(
							sprintf(
								g11n3t('GitHub item %s/%s #%d already has the %s label.'),
								$this->project->gh_user,
								$this->project->gh_project,
								$pullID,
								$issueLabel
							)
						);
						$labelSet = true;

						continue;
					}
				}

				// Add the label if we need to
				if (!$labelSet)
				{
					// Post the new label on the object
					$this->out(
						sprintf(
							g11n3t('Adding %s label to %s/%s #%d'),
							$issueLabel,
							$this->project->gh_user,
							$this->project->gh_project,
							$pullID
						)
					);

					$this->github->issues->labels->add(
						$this->project->gh_user, $this->project->gh_project, $pullID, [$issueLabel]
					);
				}
			}
		}
		else
		{
			$this->out(
				sprintf(
					g11n3t('The %s/%s project is not supported by this command at this time.'),
					$this->project->gh_user,
					$this->project->gh_project
				)
			);
		}

		return $this;
	}

	/**
	 * Updates the status of a pull request if needed
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function updatePullStatus()
	{
		// Only process for joomla/joomla-cms
		if ($this->project->gh_user == 'joomla' && $this->project->gh_project == 'joomla-cms')
		{
			$message = 'This pull request is targeted at the master branch.  Pull requests should no longer be merged to master.';

			foreach ($this->pulls as $pull)
			{
				if ($pull->base->ref == 'master')
				{
					$this->github->repositories->statuses->create(
						$this->project->gh_user, $this->project->gh_project, $pull->head->sha, 'error', null, $message
					);

					$this->out(
						sprintf(
							g11n3t('GitHub item %s/%s #%d has had its merge status set to "error".'),
							$this->project->gh_user,
							$this->project->gh_project,
							$pull->number
						)
					);
				}
			}
		}
		else
		{
			$this->out(
				sprintf(
					g11n3t('The %s/%s project is not supported by this command at this time.'),
					$this->project->gh_user,
					$this->project->gh_project
				)
			);
		}

		return $this;
	}
}
