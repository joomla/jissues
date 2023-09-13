<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace Application\Command\Clear;

use Application\Command\TrackerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for clearing all cache stores.
 *
 * @since  1.0
 */
class Allcache extends TrackerCommand
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
        $this->setName('clear:allcache');
        $this->setDescription('Clear all cache stores.');
    }

    /**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
	{
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Clearing All Cache Stores');

		(new Cache)
			->setContainer($this->getContainer())
			->execute();

		(new Twig)
			->setContainer($this->getContainer())
			->execute();

        $ioStyle->text('');
        $ioStyle->success('All cache stores have been cleared.');
	}
}
