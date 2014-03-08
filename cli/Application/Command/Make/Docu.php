<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use App\Text\Table\ArticlesTable;

/**
 * Class for parsing documentation files to inject into the site
 *
 * @since  1.0
 */
class Docu extends Make
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Compile documentation using GitHub Flavored Markdown';

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
		$files      = array();

		/* @type  \RecursiveDirectoryIterator $it */
		$it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($docuBase, \FilesystemIterator::SKIP_DOTS));

		$progressBar = $this->getApplication()->getProgressBar(count($files));

		$this->usePBar ? $this->out() : null;

		$this
			->out('Compiling documentation in: ' . $docuBase)
			->out();

		$table = new ArticlesTable($db);

		// @todo compile the md text here.
		$table->setGitHub($this->github);

		$cnt = 0;

		while ($it->valid())
		{
			if ($it->isDir())
			{
				$it->next();

				continue;
			}

			$file = new \stdClass;

			$file->filename = $it->getFilename();

			$path = $it->getSubPath();
			$page = substr($it->getFilename(), 0, strrpos($it->getFilename(), '.'));

			$this->debugOut('Compiling: ' . $page);

			$table->reset();

			$table->{$table->getKeyName()} = null;

			try
			{
				$table->load(array('alias' => $page, 'path' => $path));
			}
			catch (\RuntimeException $e)
			{
				// New item
			}

			$table->is_file = '1';
			$table->path = $it->getSubPath();
			$table->alias   = $page;
			$table->text_md = file_get_contents($it->key());

			$table->store();

			$this->usePBar
				? $progressBar->update(++$cnt)
				: $this->out('.', false);

			$it->next();
		}

		$this->out()
			->out('Finished =;)');
	}
}
