<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Update;

use JTracker\Command\Clear\Twig;
use JTracker\Command\Database\Migrate;
use JTracker\Command\Make\Repoinfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for synchronizing a server with the primary git repository
 *
 * @since  1.0
 */
class Server extends Update
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
        $this->setName('update:server');
        $this->setDescription('Updates the local installation to either a specified version or latest git HEAD for the active branch.');
        $this->addOption('app_version', null, InputOption::VALUE_REQUIRED, 'An optional version number to update to.');
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
        $ioStyle->title('Update Server');

        $this->logOut('Beginning git update');

        $version = $input->getOption('app_version');

        if ($version) {
            // Fetch from remote sources and checkout the specified version tag
            $this->execCommand('cd ' . JPATH_ROOT . ' && git fetch && git checkout ' . $version . ' 2>&1');

            $message = \sprintf('Update to version %s successful', $version);
        } else {
            // Perform a git pull on the active branch
            $this->execCommand('cd ' . JPATH_ROOT . ' && git pull 2>&1');

            $message = 'Git update Finished';
        }

        // Update the Composer installation
        $ioStyle->info('Installing current Composer dependencies and regenerating autoloader');
        $this->execCommand('cd ' . JPATH_ROOT . ' && composer install --no-dev --optimize-autoloader 2>&1');

        // Execute the database migrations (if any) for this version
        $migrateCommand = $this->getApplication()->getCommand(Migrate::COMMAND_NAME);
        $migrateCommand->execute($input, $output);

        // Flush the Twig cache
        $twigCommand = $this->getApplication()->getCommand(Twig::COMMAND_NAME);
        $twigCommand->execute($input, $output);

        $repoInfoCommand = $this->getApplication()->getCommand(Repoinfo::COMMAND_NAME);
        $repoInfoCommand->execute($input, $output);

        $this->logOut($message);
        $ioStyle->info($message);

        $this->logOut('Update Finished');

        return Command::SUCCESS;
    }
}
