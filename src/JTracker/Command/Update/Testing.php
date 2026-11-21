<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Command\Update;

use App\Projects\TrackerProject;
use App\Tracker\Table\IssuesTable;
use App\Tracker\Testing\IssueContext;
use App\Tracker\Testing\TestResultServiceFactory;
use JTracker\Github\GithubFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Prepares the open pull requests of a project for testing on GitHub.
 *
 * Run once after enabling "Record test results with /tested commands on GitHub" for a project: calculates the diff
 * fingerprints, keeps the test results recorded for the current head commits and writes the test section,
 * the commit status and the labels of every open pull request.
 *
 * @since  1.0
 */
class Testing extends Update
{
    /**
     * Configure the command.
     *
     * @return  void
     *
     * @since   1.0
     */
    protected function configure(): void
    {
        $this->setName('update:testing');
        $this->setDescription('Prepares the open pull requests of a project for recording test results on GitHub.');

        $this->addProjectOption();
        $this->addOption('issue', null, InputOption::VALUE_REQUIRED, 'Only process the pull request with this number.');
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
        $ioStyle->title('Prepare Pull Requests for Testing on GitHub');

        $this->selectProject($input, $ioStyle);

        /** @var \Joomla\Database\DatabaseInterface $db */
        $db = $this->getContainer()->get('db');

        $project = new TrackerProject(
            $db,
            $db->setQuery(
                $db->getQuery(true)
                    ->select('*')
                    ->from($db->quoteName('#__tracker_projects'))
                    ->where($db->quoteName('project_id') . ' = ' . (int) $this->project->project_id)
            )->loadObject()
        );

        if (!$project->hasGithubTesting()) {
            $ioStyle->error(
                \sprintf(
                    'The project %s does not record test results on GitHub. Enable the option in the project settings first.',
                    $project->title
                )
            );

            return Command::FAILURE;
        }

        $github  = GithubFactory::getProjectBotInstance($this->getApplication(), $project);
        $service = TestResultServiceFactory::create($this->getContainer(), $project, $github);

        $query = $db->getQuery(true)
            ->select($db->quoteName('i.id'))
            ->from($db->quoteName('#__issues', 'i'))
            ->innerJoin($db->quoteName('#__status', 's') . ' ON ' . $db->quoteName('s.id') . ' = ' . $db->quoteName('i.status'))
            ->where($db->quoteName('i.project_id') . ' = ' . (int) $project->project_id)
            ->where($db->quoteName('i.has_code') . ' = 1')
            ->where($db->quoteName('s.closed') . ' = 0')
            ->order($db->quoteName('i.issue_number'));

        if ($input->getOption('issue')) {
            $query->where($db->quoteName('i.issue_number') . ' = ' . (int) $input->getOption('issue'));
        }

        $ids = $db->setQuery($query)->loadColumn();

        $ioStyle->text(\sprintf('Processing %d open pull requests of %s/%s.', \count($ids), $project->gh_user, $project->gh_project));

        $done   = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $table = (new IssuesTable($db))->load($id);

            try {
                $service->initialise(IssueContext::fromTable($table, true));

                $done++;
                $this->out(\sprintf('#%d done', $table->issue_number));
            } catch (\Exception $e) {
                $failed++;
                $ioStyle->warning(\sprintf('#%d failed: %s', $table->issue_number, $e->getMessage()));
            }
        }

        $ioStyle->success(\sprintf('Processed %d pull requests, %d failed. The command can be run again safely.', $done, $failed));

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
