<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

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

		$this->github = $this->getContainer()->get('gitHub');

		$this->getApplication()->displayGitHubRateLimit();

		$docuBase = JPATH_ROOT . '/Documentation';

		/* @type  \RecursiveDirectoryIterator $iterator */
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($docuBase, \FilesystemIterator::SKIP_DOTS)
		);

		$this
			->out('Compiling documentation in: ' . $docuBase)
			->out();

		/* @type \JTracker\Model\AbstractDoctrineItemModel $model */
		$model = $this->getApplication()->getDoctrineModel('ShowModel', 'Documentor');

		while ($iterator->valid())
		{
			if ($iterator->isDir())
			{
				$iterator->next();

				continue;
			}

			$path = $iterator->getSubPath();
			$page = substr($iterator->getFilename(), 0, strrpos($iterator->getFilename(), '.'));

			$this->debugOut(sprintf('Compiling: %s - %s', $path, $page));

			$article = $model->findOneBy(['page' => $page, 'path' => $path]);

			$data = [];

			$data['id'] = null;

			if ($article)
			{
				$data['id'] = $article->getId();
			}

			$data['path'] = $iterator->getSubPath();
			$data['page'] = $page;
			$data['text'] = $this->github->markdown->render(file_get_contents($iterator->key()));

			$model->save($data);

			$this->out('.', false);

			$iterator->next();
		}

		$this->out()
			->out('Finished =;)');
	}
}
