<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace CliApp\Command\Make;

use App\Text\Table\ArticlesTable;

/**
 * Class for retrieving issues from GitHub for selected projects
 *
 * @since  1.0
 */
class Docu extends Make
{
	/**
	 * Constructor.
	 *
	 * @since   1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->description = 'Compile documentation using GitHub markdown.';
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
		$this->getApplication()->outputTitle('Make Documentation');

		$this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

		if ($this->getApplication()->input->get('noprogress'))
		{
			$this->usePBar = false;
		}

		$this->github = $this->container->get('gitHub');

		$this->getApplication()->displayGitHubRateLimit();

		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->container->get('db');

		$docuBase   = JPATH_ROOT . '/Documentation';
		$pagePrefix = 'dox-';
		$files      = array();

		/* @type \DirectoryIterator $f */
		foreach (new \DirectoryIterator($docuBase) as $f)
		{
			if ($f->isDot())
			{
				continue;
			}

			$files[] = $f->getFilename();
		}

		$progressBar = $this->getApplication()->getProgressBar(count($files));

		$this->usePBar ? $this->out() : null;

		$this
			->out('Compiling documentation in: ' . $docuBase)
			->out('Page prefix: ' . $pagePrefix)
			->out();

		$table = new ArticlesTable($db);

		foreach ($files as $i => $file)
		{
			// @todo enough for URIs ?
			$page = $pagePrefix . substr($file, 0, strrpos($file, '.'));

			$this->debugOut('Compiling: ' . $page);

			$table->reset();
			$table->{$table->getKeyName()} = null;

			try
			{
				$table->load(array('alias' => $page));
			}
			catch (\RuntimeException $e)
			{
				// New item
			}

			$table->alias   = $page;
			$table->text_md = file_get_contents($docuBase . '/' . $file);

			$table->store();

			$this->usePBar
				? $progressBar->update($i + 1)
				: $this->out('.', false);
		}

		$this->out()
			->out('Finished =;)');
	}
}
