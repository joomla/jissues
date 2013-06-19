<?php
/**
 * @package    JTracker\Components\Users
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Hooks\Ajax;

use JTracker\Controller\AbstractTrackerController;

/**
 * Default controller class for the Users component.
 *
 * @package  JTracker\Components\Users
 * @since    1.0
 */
class Modify extends AbstractTrackerController
{
	/**
	 * Execute the controller.
	 *
	 * @since  1.0
	 *
	 * @return  boolean
	 */
	public function execute()
	{
		$response = new \stdClass;

		$response->data  = new \stdClass;
		$response->error = '';
		$response->message = '';

		ob_start();

		try
		{
			$this->getApplication()->getUser()->authorize('admin');

			$action = $this->getInput()->getCmd('action');
			$hookId = $this->getInput()->getInt('hook_id');

			$project = $this->getApplication()->getProject();

			// Get a valid hook object
			$hook = $this->getHook($hookId);

			if ('delete' == $action)
			{
				// Delete the hook
				$this->getApplication()->getGitHub()
					->repositories->hooks->delete(
						$project->gh_user, $project->gh_project, $hookId
					);
			}
			else
			{
				// Process other actions
				$this->processAction($action, $hook);
			}

			// Get the current hooks list.
			$response->data = $this->getApplication()->getGitHub()
				->repositories->hooks->getList(
					$project->gh_user, $project->gh_project
				);
		}
		catch (\Exception $e)
		{
			$response->error = $e->getMessage();
		}

		$errors = ob_get_clean();

		if ($errors)
		{
			$response->error .= $errors;
		}

		header('Content-type: application/json');

		echo json_encode($response);

		exit(0);
	}

	/**
	 * Process an action.
	 *
	 * @param   string  $action  The action to perform.
	 * @param   object  $hook    The hook object.
	 *
	 * @throws \RuntimeException
	 * @return  $this
	 */
	private function processAction($action, $hook)
	{
		$project = $this->getApplication()->getProject();

		switch ($action)
		{
			case 'activate' :
				$hook->active = true;
				break;

			case 'deactivate' :
				$hook->active = false;
				break;

			default :
				throw new \RuntimeException('Invalid action');
				break;
		}

		// Create the hook.
		$this->getApplication()->getGitHub()
			->repositories->hooks->edit(
			$project->gh_user,
			$project->gh_project,
			$hook->id,
			$hook->name,
			$hook->config,
			$hook->events,
			array(),
			array(),
			$hook->active
		);

		return $this;
	}

	/**
	 * Get a valid hook.
	 *
	 * @param   integer  $hookId  The hook id.
	 *
	 * @throws \RuntimeException
	 * @since  1.0
	 * @return object
	 */
	private function getHook($hookId)
	{
		$project = $this->getApplication()->getProject();

		$hooks = $this->getApplication()->getGitHub()
			->repositories->hooks->getList(
			$project->gh_user, $project->gh_project
		);

		if (!$hooks)
		{
			throw new \RuntimeException('No hooks found in repository');
		}

		foreach ($hooks as $hook)
		{
			if ($hook->id == $hookId)
			{
				return $hook;
			}
		}

		throw new \RuntimeException('Unknown hook');
	}
}
