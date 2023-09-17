<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use Application\Command\TrackerCommand;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for clearing the Twig cache.
 *
 * @since  1.0
 */
class Twig extends TrackerCommand
{
	/**
	 * Configure the command.
	 *
	 * @return  void
	 *
	 * @since   2.0.0
	 */
	protected function configure(): void
	{
		$this->setName('clear:twig');
		$this->setDescription('Clear the Twig cache.');
	}

	/**
	 * Execute the command.
	 *
	 * @param   InputInterface   $input   The input to inject into the command.
	 * @param   OutputInterface  $output  The output to inject into the command.
	 *
	 * @return  integer
	 *
	 * @since   1.0
	 */
	protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
		$ioStyle = new SymfonyStyle($input, $output);
		$ioStyle->title('Clear Twig Cache Directory');

		if (!$this->getApplication()->get('renderer.cache', false))
		{
			$ioStyle->info('Twig caching is not enabled.');

			return 0;
		}

		$cacheDir     = JPATH_ROOT . '/cache';
		$twigCacheDir = $this->getApplication()->get('renderer.cache');

		$this->logOut(sprintf('Cleaning the cache dir in "%s"', $cacheDir . '/' . $twigCacheDir));

		$filesystem = new Filesystem(new Local($cacheDir));

		if ($filesystem->has($twigCacheDir))
		{
			$filesystem->deleteDir($twigCacheDir);
		}

		$ioStyle->newLine();
		$ioStyle->success('The Twig cache directory has been cleared.');

		return 0;
	}
}
