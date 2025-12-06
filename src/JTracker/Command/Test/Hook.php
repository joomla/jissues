<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Test;

use App\Projects\TrackerProject;
use Joomla\Github\Github;
use Joomla\Input\Input;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class for testing web hooks.
 *
 * @since  1.0
 */
class Hook extends Test
{
    /**
     * Hook controller
     *
     * @var    \App\Tracker\Controller\AbstractHookController
     * @since  1.0
     */
    protected $controller;

    /**
     * Joomla! Github object
     *
     * @var    Github
     * @since  1.0
     */
    protected $github;

    /**
     * The project object.
     *
     * @var    TrackerProject
     * @since  1.0
     */
    protected $project;

    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   2.0.0
     */
    protected function configure(): void
    {
        $this->setName('test:hook');
        $this->setDescription('Tests web hooks.');
        $this->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'Process the project with the given ID.');
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
        $ioStyle->title('Test Hooks');

        // TODO: Fix me!!!
        $ioStyle->error(
            \sprintf(
                'The test hook command is broken since the migration of the CLI to the new console class. 
				A full rewrite is required before it will correctly function to remove the dependency of the controller
				class on %s',
                Input::class
            )
        );

        return Command::FAILURE;

        $this->logOut('Start testing hook');

        $this->selectProject($input, $ioStyle)->selectHook($ioStyle);

        $this->getApplication()->input->set('project', $this->project->project_id);

        $this->setupGitHub();

        $result = $this->controller->execute();
        $this->logOut($result);

        return Command::SUCCESS;
    }

    /**
     * Select the hook.
     *
     * @param   SymfonyStyle  $io  Output decorator
     *
     * @return  $this
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    protected function selectHook(SymfonyStyle $io)
    {
        $paths = (new Filesystem(new LocalFilesystemAdapter(JPATH_ROOT . '/src/App/Tracker/Controller/Hooks')))->listContents();
        $hooks = [];

        foreach ($paths as $path) {
            if ($path['type'] == 'file') {
                $hooks[] = str_replace(['Receive', 'Hook'], '', $path['filename']);
            }
        }

        $io->newLine();
        $io->text('Available hooks:');
        $io->newLine();

        $cnt = 1;

        $checks = [];

        foreach ($hooks as $hook) {
            $io->text('  <b>' . $cnt . '</b> ' . $hook);
            $checks[$cnt] = $hook;
            $cnt++;
        }

        $io->newLine();
        $question = new ChoiceQuestion('Select a hook:', array_keys($checks));
        $resp     = (int) $io->askQuestion($question);

        $classname = '\\App\\Tracker\\Controller\\Hooks\\Receive' . $checks[$resp] . 'Hook';

        // Initialize the hook controller
        $this->controller = new $classname();
        $this->controller->setContainer($this->getContainer());

        if ($this->project->project_id == 1 && $resp === 4) {
            $this->getApplication()->input->post->set('payload', file_get_contents(__DIR__ . '/data/cms-pull.json'));
        }

        $this->controller->initialize();

        return $this;
    }

    /**
     * Select the project.
     *
     * @param   InputInterface  $input  The input to inject into the command.
     * @param   SymfonyStyle    $io     The output decorator.
     *
     * @return  $this
     *
     * @throws  \RuntimeException
     * @throws  \InvalidArgumentException
     * @since   1.0
     */
    protected function selectProject(InputInterface $input, SymfonyStyle $io): self
    {
        /** @var \Joomla\Database\DatabaseDriver $db */
        $db = $this->getContainer()->get('db');

        $projects = $db->setQuery(
            $db->getQuery(true)
                ->from($db->quoteName('#__tracker_projects'))
                ->select(['project_id', 'title', 'gh_user', 'gh_project'])
        )->loadObjectList();

        $id = (int) $input->getOption('project');

        if (!$id) {
            $io->newLine();
            $io->text('<b>Available projects:</b>');
            $io->newLine();

            $cnt = 1;

            $checks = [];

            foreach ($projects as $project) {
                if ($project->gh_user && $project->gh_project) {
                    $io->text('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
                    $checks[$cnt] = $project;
                    $cnt++;
                }
            }

            $io->newLine();
            $question = new ChoiceQuestion('Select a project:', array_keys($checks));
            $resp     = (int) $io->askQuestion($question);

            $this->project = $checks[$resp];
        } else {
            foreach ($projects as $project) {
                if ($project->project_id == $id) {
                    $this->project = $project;

                    break;
                }
            }

            if ($this->project === null) {
                throw new \InvalidArgumentException('Invalid project');
            }
        }

        $this->logOut('Processing project: <info>' . $this->project->title . '</info>');

        return $this;
    }

    /**
     * Setup the Github object.
     *
     * @return  $this
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    protected function setupGitHub()
    {
        $this->github = $this->getContainer()->get('gitHub');

        return $this;
    }
}
