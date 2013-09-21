<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Get;

use App\Projects\Table\LabelsTable;

use JTracker\Container;

/**
 * Class for retrieving labels from GitHub for selected projects.
 *
 * @since  1.0
 */
class Labels extends Get
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Retrieve project labels from GitHub.';
	}

	/**
	 * Execute the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->application->outputTitle('Retrieve Labels');

		$this->logOut('Start retrieve Labels')
			->selectProject()
			->setupGitHub()
			->displayGitHubRateLimit()
			->processLabels()
			->out()
			->logOut('Finished');
	}

	/**
	 * Get the projects labels.
	 *
	 * @return $this
	 *
	 * @since  1.0
	 */
	protected function processLabels()
	{
		$this->out('Fetching labels...', false);

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = Container::getInstance()->get('db');

		$table = new LabelsTable($db);

		$labels = $this->github->issues->labels->getList(
			$this->project->gh_user, $this->project->gh_project
		);

		$names = array();

		$cntChanged = 0;
		$cntNew = 0;

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
				if ($table->color != $label->color)
				{
					$table->color = $label->color;

					$table->store();

					++ $cntChanged;
				}
			}
			catch (\RuntimeException $e)
			{
				// New label
				$table->project_id = $this->project->project_id;
				$table->name       = $label->name;
				$table->color      = $label->color;

				$table->store();

				++ $cntNew;
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
					->where($db->quoteName('label_id') . ' IN (' . implode(', ', $ids) . ')')
			)->execute();
		}

		return $this->out('ok')
			->logOut(
				sprintf(
					'Labels: %1$d changed, %2$d new, %3$d deleted.',
					$cntChanged, $cntNew, count($ids)
				)
			);
	}
}
