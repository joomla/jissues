<?php
/**
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Projects\Table\LabelsTable;

use CliApp\Application\TrackerApplication;

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
	 * @param   TrackerApplication  $application  The application object.
	 *
	 * @since   1.0
	 */
	public function __construct(TrackerApplication $application)
	{
		$this->application = $application;

		$this->description = 'Get the project info from GitHub.';

		$this->usePBar = $this->application->get('cli-application.progress-bar');

		if ($this->application->input->get('noprogress'))
		{
			$this->usePBar = false;
		}
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

		$this->selectProject()
			->setupGitHub()
			->displayGitHubRateLimit();

		$this->out(
			sprintf(
				'Updating project info for project: %s/%s',
				$this->project->gh_user,
				$this->project->gh_project
			)
		);

		// Process the data from GitHub
		$this->processLabels();

		$this->out()
			->out('Finished');
	}

	/**
	 * Get the projects labels.
	 *
	 * @since  1.0
	 * @return $this
	 */
	protected function processLabels()
	{
		$this->out('Fetching labels...', false);

		$db = $this->application->getDatabase();

		$table = new LabelsTable($db);

		$labels = $this->github->issues->labels->getList(
			$this->project->gh_user, $this->project->gh_project
		);

		$names = array();

		foreach ($labels as $label)
		{
			try
			{
				$table->label_id = null;

				// Check if the label exists
				$table->load(
					array(
						'project_id' => $this->project->project_id,
						'name'       => $label->name
					)
				);

				// Values that may have changed
				$table->color = $label->color;

				$table->store();
			}
			catch (\RuntimeException $e)
			{
				// New label
				$table->project_id = $this->project->project_id;
				$table->name       = $label->name;
				$table->color      = $label->color;

				$table->store();
			}

			$names[] = $label->name;
		}

		// Check for deleted labels
		$ids = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName($table->getTableName()))
				->select('label_id')
				->where($db->quoteName('project_id') . ' = ' . $this->project->project_id)
				->where($db->quoteName('name') . ' NOT IN (\'' . implode("', '", $names) . '\')')
		)->loadRowList();

		if ($ids)
		{
			// Kill the orphans
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->quoteName($table->getTableName()))
					->where(
						$db->quoteName('label_id') . ' IN ('
						. implode(', ', $ids)
						. ')'
					)
			)->execute();
		}

		return $this->out('ok');
	}
}
