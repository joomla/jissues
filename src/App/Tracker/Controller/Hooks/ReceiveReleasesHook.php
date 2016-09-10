<?php
/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\Hooks;

use App\Projects\TrackerProject;
use App\Tracker\Controller\AbstractHookController;
use App\Tracker\Model\IssueModel;
use App\Tracker\Model\ReleaseModel;
use App\Tracker\Table\ActivitiesTable;
use App\Tracker\Table\IssuesTable;

use App\Tracker\Table\ReleasesTable;
use Joomla\Date\Date;

/**
 * Controller class receive and inject releases from GitHub
 *
 * @since  1.0
 */
class ReceiveReleasesHook extends AbstractHookController
{
	/**
	 * The type of hook being executed
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $type = 'releases';

	/**
	 * Prepare the response.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
	{
		$releaseId = null;

		try
		{
			// Check to see if the release is already in the database
			$releaseId = $this->db->setQuery(
				$this->db->getQuery(true)
					->select($this->db->quoteName('release_id'))
					->from($this->db->quoteName('#__releases'))
					->where($this->db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
					->where($this->db->quoteName('release_id') . ' = ' . (int) $this->hookData->release->id)
			)->loadResult();
		}
		catch (\RuntimeException $e)
		{
			$this->logger->error('Error checking the database for release ID', ['exception' => $e]);
			$this->getContainer()->get('app')->close();
		}

		// If the item is already in the database, update it; else, insert it
		if ($releaseId)
		{
			// TODO - GitHub says they only send a "published" event when the release is created, if it handles updates we'll need to add support
		}
		else
		{
			$this->insertRelease();
		}

		$this->response->message = 'Hook data processed successfully.';
	}

	/**
	 * Method to insert data for a release from GitHub
	 *
	 * @return  boolean  True on success
	 *
	 * @since   1.0
	 */
	protected function insertRelease()
	{
		// Prepare the dates for insertion to the database
		$dateFormat = $this->db->getDateFormat();

		$data = [
			'release_id' => $this->hookData->release->id,
			'name'       => $this->hookData->release->name,
			'tag_name'   => $this->hookData->release->tag_name,
			'created_at' => (new Date($this->hookData->release->created_at))->format($dateFormat),
			'notes'      => $this->parseText($this->hookData->release->notes),
			'notes_raw'  => $this->hookData->release->notes,
		];

		try
		{
			(new ReleaseModel($this->db))
				->setProject(new TrackerProject($this->db, $this->project))
				->add($data);
		}
		catch (\Exception $e)
		{
			$this->logger->error(
				sprintf(
					'Error adding GitHub release ID %d for the %s/%s project to the tracker',
					$this->hookData->release->id,
					$this->project->gh_user,
					$this->project->gh_project
				),
				['exception' => $e]
			);

			$this->getContainer()->get('app')->close();
		}

		// Get a table object for the new record to process in the event listeners
		$table = (new ReleasesTable($this->db))
			->load($this->db->insertid());

		$this->triggerEvent('onReleaseAfterCreate', ['table' => $table, 'action' => $this->hookData->action]);

		return true;
	}
}
