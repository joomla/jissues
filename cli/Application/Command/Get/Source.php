<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Get;

use Application\Command\Get\Project;

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local as Adapter;

/**
 * Class for retrieving the source files from GitHub for selected projects.
 *
 * @since  1.0
 */
class Source extends Project
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = g11n3t('Retrieve project source files from GitHub.');
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
		$this->getApplication()->outputTitle(g11n3t('Retrieve Sources'));

		$this->logOut(g11n3t('Start retrieve sources'))
			->selectProject()
			->setupGitHub()
			->getSources()
			->out()
			->logOut(g11n3t('Finished'));
	}

	/**
	 * Get source files from GitHub.
	 *
	 * @return $this
	 *
	 * @since   1.0
	 */
	protected function getSources()
	{
		$filesystem = new Filesystem(new Adapter($this->project->getSourcePath()));

		// We only "check out" some template files (for now)

		try
		{
			// Fetch the contents of the '.github' folder.
			$files = $this->github->repositories->contents->get(
				$this->project->getGh_User(),
				$this->project->getGh_Project(),
				'.github'
			);

			if ($files)
			{
				foreach ($files as $file)
				{
					$fileContents = $this->github->repositories->contents->get(
						$this->project->getGh_User(),
						$this->project->getGh_Project(),
						$file->path
					);

					$result = $filesystem->put($fileContents->path, base64_decode($fileContents->content));

					if (false == $result)
					{
						throw new \RuntimeException('Can not write the file');
					}
				}
			}
		}
		catch (\DomainException $exception)
		{
			// The '.github' folder does not exist.
		}

		// Fetch some template files from the root.
		$files = $this->github->repositories->contents->get(
			$this->project->getGh_User(),
			$this->project->getGh_Project(),
			'/'
		);

		$knownFiles = ['ISSUE_TEMPLATE'];

		foreach ($files as $file)
		{
			if ($file->type != 'file')
			{
				continue;
			}

			foreach ($knownFiles as $knownFile)
			{
				if (strpos($file->name, $knownFile) === 0)
				{
					$fileContents = $this->github->repositories->contents->get(
						$this->project->getGh_User(),
						$this->project->getGh_Project(),
						$file->path
					);

					$result = $filesystem->put($fileContents->path, base64_decode($fileContents->content));

					if (false == $result)
					{
						throw new \RuntimeException('Can not write the file');
					}
				}
			}
		}

		return $this;
	}
}
