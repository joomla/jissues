<?php
/**
 * Part of the Joomla Tracker's GitHub Application
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace App\GitHub\Controller\Ajax\Hooks;

use JTracker\Controller\AbstractAjaxController;

/**
 * Controller class to modify webhooks on the GitHub repository.
 *
 * @since  1.0
 */
class Modify extends AbstractAjaxController
{
	/**
	 * Prepare the response.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function prepareResponse()
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
		$this->response->data = $this->getApplication()->getGitHub()
			->repositories->hooks->getList(
				$project->gh_user, $project->gh_project
			);
	}

	/**
	 * Process an action.
	 *
	 * @param   string  $action  The action to perform.
	 * @param   object  $hook    The hook object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
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
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
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
