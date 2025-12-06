<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Make;

use App\Text\Table\ArticlesTable;
use JTracker\Command\TrackerCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for parsing documentation files to inject into the site
 *
 * @since  1.0
 */
class Docu extends TrackerCommand
{
    /**
     * Joomla! Github object
     *
     * @var    \Joomla\Github\Github
     * @since  1.0
     */
    protected $github;

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('make:docu');
        $this->setDescription('Compile documentation using GitHub Flavored Markdown.');
        $this->addOption('noprogress', null, InputOption::VALUE_NONE, "Don't use a progress bar.");
    }

    /**
     * Execute the command.
     *
     * @param   InputInterface   $input   The input to inject into the command.
     * @param   OutputInterface  $output  The output to inject into the command.
     *
     * @return  integer
     *
     * @throws  \RuntimeException
     * @throws  \UnexpectedValueException
     * @since   1.0
     */
    protected function doExecute(InputInterface $input, OutputInterface $output): int
    {
        $ioStyle = new SymfonyStyle($input, $output);
        $ioStyle->title('Make Documentation');

        $this->usePBar = $this->getApplication()->get('cli-application.progress-bar');

        if ($input->getOption('noprogress')) {
            $this->usePBar = false;
        }

        $this->github = $this->getContainer()->get('gitHub');

        $this->displayGitHubRateLimit($ioStyle);

        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $docuBase   = JPATH_ROOT . '/Documentation';

        /** @var  \RecursiveDirectoryIterator $it */
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($docuBase, \FilesystemIterator::SKIP_DOTS));

        $ioStyle->text(\sprintf('Compiling documentation in: %s', $docuBase));
        $ioStyle->newLine();

        while ($it->valid()) {
            if ($it->isDir()) {
                $it->next();

                continue;
            }

            $file = new \stdClass();

            $file->filename = $it->getFilename();

            $path = $it->getSubPath();
            $page = substr($it->getFilename(), 0, strrpos($it->getFilename(), '.'));

            if ($output->isVeryVerbose()) {
                $ioStyle->text(\sprintf('Compiling: %s', $page));
            }

            $table = new ArticlesTable($db);

            $table->{$table->getKeyName()} = null;

            try {
                $table->load(['alias' => $page, 'path' => $path]);
            } catch (\RuntimeException $e) {
                // New item
            }

            $table->is_file = '1';
            $table->path    = $it->getSubPath();
            $table->alias   = $page;
            $table->text_md = file_get_contents($it->key());
            $table->text    = $this->github->markdown->render($table->text_md);

            $table->store();

            $ioStyle->text('.');

            $it->next();
        }

        $ioStyle->newLine();
        $ioStyle->success('Finished.');

        return Command::SUCCESS;
    }
}
