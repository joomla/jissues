<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Make;

use App\Documentor\Entity\Document;

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
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($docuBase, \FilesystemIterator::SKIP_DOTS));

		$this
			->out('Compiling documentation in: ' . $docuBase)
			->out();

		$entityManager = $this->getContainer()->get('EntityManager');

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

			$article = $entityManager->getRepository('App\Documentor\Entity\Document')
				->findOneBy(['page' => $page, 'path' => $path]);

			if (!$article)
			{
				// New item
				$article = new Document;
			}

			$article
				->setPath($iterator->getSubPath())
				->setPage($page)
				->setText($this->github->markdown->render(file_get_contents($iterator->key())));

			$entityManager->persist($article);

			// Flush right away to get some visual feed back
			$entityManager->flush();

			$this->out('.', false);

			$iterator->next();
		}

		$this->out()
			->out('Finished =;)');
	}
}
