<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get\Project;

use App\Projects\Table\LabelsTable;

use Application\Command\Get\Project;

/**
 * Class for retrieving labels from GitHub for selected projects.
 *
 * @since  1.0
 */
class Labels extends Project
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Retrieve project labels from GitHub.';

	/**
	 * Execute the command.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->getApplication()->outputTitle('Retrieve Labels');

		$this->logOut('Start retrieve Labels')
			->selectProject()
			->setupGitHub()
			->processLabels()
			->out()
			->logOut('Finished');
	}

	/**
	 * Get the project labels.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function processLabels()
	{
		$this->out('Fetching labels...', false);

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->container->get('db');

		$table = new LabelsTable($db);

		$labels = $this->github->issues->labels->getList(
			$this->project->gh_user, $this->project->gh_project
		);

		$names = array();

		$cntUpdated = 0;
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

					++ $cntUpdated;
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

		$cntDeleted = count($ids);

		return $this->out('ok')
			->logOut(
				sprintf(
					'Labels: %1$d new, %2$d updated, %3$d deleted.',
					$cntNew, $cntUpdated, $cntDeleted
				)
			);
	}
}
