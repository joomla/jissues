<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Test;

use App\Projects\TrackerProject;

use Application\Exception\AbortException;

use Joomla\Github\Github;
use Joomla\Filesystem\Folder;

/**
 * Class for testing web hooks.
 *
 * @since  1.0
 */
class Hook extends Test
{
	/**
	 * Hook controller
	 *
	 * @var    \App\Tracker\Controller\AbstractHookController
	 * @since  1.0
	 */
	protected $controller;

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Tests web hooks';

	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * The project object.
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		// Define JPATH_THEMES as it is used in the hooks
		define('JPATH_THEMES', JPATH_ROOT . '/www');

		$this->getApplication()->outputTitle('Test Hooks');

		$this->logOut('Start testing hook');

		$this->selectProject()->selectHook();

		$this->getApplication()->input->set('project', $this->project->project_id);

		$this->setupGitHub();

		$this->controller->execute();
	}

	/**
	 * Select the hook.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectHook()
	{
		$files = Folder::files(JPATH_ROOT . '/src/App/Tracker/Controller/Hooks');
		$hooks = array();

		foreach ($files as $file)
		{
			$hooks[] = str_replace(array('Receive', 'Hook.php'), '', $file);
		}

		$this->out()
			->out('<b>Available hooks:</b>')
			->out();

		$cnt = 1;

		$checks = array();

		foreach ($hooks as $hook)
		{
			$this->out('  <b>' . $cnt . '</b> ' . $hook);
			$checks[$cnt] = $hook;
			$cnt++;
		}

		$this->out()
			->out('<question>Select a hook:</question> ', false);

		$resp = (int) trim($this->getApplication()->in());

		if (!$resp)
		{
			throw new AbortException('Aborted');
		}

		if (false == array_key_exists($resp, $checks))
		{
			throw new AbortException('Invalid hook');
		}

		$classname = '\\App\\Tracker\\Controller\\Hooks\\Receive' . $checks[$resp] . 'Hook';

		// Initialize the hook controller
		$this->controller = new $classname;
		$this->controller->setContainer($this->container);

		if ($this->project->project_id === '1' && $resp === 3)
		{
			$this->getApplication()->input->post->set('payload', file_get_contents(__DIR__ . '/data/cms-pull.json'));
		}

		$this->controller->initialize();

		return $this;
	}

	/**
	 * Select the project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectProject()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$projects = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__tracker_projects'))
				->select(array('project_id', 'title', 'gh_user', 'gh_project'))

		)->loadObjectList();
/*
		$projectsModel = new ProjectsModel($this->getContainer()->get('db'), $this->getApplication()->input);
		$user = new GitHubUser($this->getApplication()->getp);
		$projects = with()->getItems();
*/
		$id = $this->getApplication()->input->getInt('project', $this->getApplication()->input->getInt('p'));

		if (!$id)
		{
			$this->out()
				->out('<b>Available projects:</b>')
				->out();

			$cnt = 1;

			$checks = array();

			foreach ($projects as $project)
			{
				if ($project->gh_user && $project->gh_project)
				{
					$this->out('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
					$checks[$cnt] = $project;
					$cnt++;
				}
			}

			$this->out()
				->out('<question>Select a project:</question> ', false);

			$resp = (int) trim($this->getApplication()->in());

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (false == array_key_exists($resp, $checks))
			{
				throw new AbortException('Invalid project');
			}

			$this->project = $checks[$resp];
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->project_id == $id)
				{
					$this->project = $project;

					break;
				}
			}

			if (is_null($this->project))
			{
				throw new AbortException('Invalid project');
			}
		}

		$this->logOut('Processing project: <info>' . $this->project->title . '</info>');

		return $this;
	}

	/**
	 * Setup the Github object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupGitHub()
	{
		$this->github = $this->container->get('gitHub');

		return $this;
	}
}
