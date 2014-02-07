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

		// @todo compile the md text here.
		$table->setGitHub($this->github);

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
