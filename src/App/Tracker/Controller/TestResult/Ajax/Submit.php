<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Controller\TestResult\Ajax;

use App\Projects\TrackerProject;
use App\Tracker\Model\ActivityModel;
use App\Tracker\Model\IssueModel;
use App\Tracker\Table\IssuesTable;
use App\Tracker\Testing\IssueContext;
use App\Tracker\Testing\TestResult;
use App\Tracker\Testing\TestResultRepository;
use App\Tracker\Testing\TestResultServiceFactory;
use App\Tracker\Testing\TestSummary;
use JTracker\Controller\AbstractAjaxController;
use JTracker\Github\GithubFactory;
use JTracker\Helper\GitHubHelper;

/**
 * Add test result controller class.
 *
 * @since  1.0
 */
class Submit extends AbstractAjaxController
{
    /**
     * Prepare the response.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  \Exception
     */
    protected function prepareResponse()
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');
        $user        = $application->getUser();
        $project     = $application->getProject();

        if (!$user->id || !$user->check('view')) {
            throw new \Exception('You are not allowed to test this item.');
        }

        $issueId     = $application->getInput()->getUint('issueId');
        $result      = $application->getInput()->getUint('result');
        $userComment = $application->getInput()->get('comment', '', 'raw');
        $sha         = $application->getInput()->getCmd('sha');

        if (!$issueId) {
            throw new \Exception('No issue ID received.');
        }

        if (!$sha) {
            throw new \Exception('Missing commit SHA.');
        }

        if (!\in_array($result, [0, 1, 2], true)) {
            throw new \UnexpectedValueException('Unexpected test result value.');
        }

        $this->setDispatcher($application->getDispatcher());
        $this->addEventListener('tests');

        $this->setProjectGitHubBot($project);

        $issueModel = new IssueModel($this->getContainer()->get('db'));

        $data = new \stdClass();

        // Make sure the issue belongs to the current project.
        $issueNumber = $issueModel->getIssueNumberById($issueId, $project->project_id);

        if (!$issueNumber) {
            throw new \Exception('Invalid issue ID.');
        }

        // With testing on GitHub the result is posted as a `/tested` command and recorded like any other command
        if ($project->hasGithubTesting()) {
            $this->submitOnGithub($project, $issueId, (int) $issueNumber, $result, (string) $userComment, $sha);

            return;
        }

        $data->testResults = $issueModel->saveTest($issueId, $user->username, $result, $sha);

        $event = (new ActivityModel($this->getContainer()->get('db')))
            ->addActivityEvent(
                'test_item',
                'now',
                $user->username,
                $project->project_id,
                $issueNumber,
                null,
                json_encode($result)
            );

        $this->triggerEvent('onTestAfterSubmit', ['issueNumber' => $issueNumber, 'data' => $data->testResults]);

        $data->event = new \stdClass();

        foreach ($event as $k => $v) {
            $data->event->$k = $v;
        }

        $data->event->text = json_decode($data->event->text);

        $gitHubHelper = new GitHubHelper($this->getContainer()->get('gitHub'));

        // Create a comment to submitted on GitHub.
        switch ($result) {
            case 0:
                $comment = 'I have not tested this item.';

                break;

            case 1:
                $comment = 'I have tested this item :white_check_mark: successfully on ' . $sha;

                break;

            case 2:
                $comment = 'I have tested this item :red_circle: unsuccessfully on ' . $sha;

                break;
            default:
                throw new \UnexpectedValueException('Unexpected test result value.');

                break;
        }

        $comment .= ($userComment) ? '<br /><br />' . $userComment : '';
        $comment .= $gitHubHelper->getApplicationComment($application, $project, $issueNumber);

        $data->comment = $gitHubHelper->addComment($project, $issueNumber, $comment, $user->username, $this->getContainer()->get('db'));

        $this->response->data = json_encode($data);

        $this->response->message = 'Test successfully added';
    }

    /**
     * Submit a test result for a project which records test results on GitHub.
     *
     * The result is posted as a `/tested` comment with the token of the user, so the comment is the visible record on GitHub.
     * It is recorded right away; the webhook for the comment finds the result by the comment ID and does not record it twice.
     *
     * @param   TrackerProject  $project      The project.
     * @param   integer         $issueId      The issue ID.
     * @param   integer         $issueNumber  The issue number.
     * @param   integer         $result       The test result (0 = withdraw, 1 = success, 2 = failure).
     * @param   string          $userComment  The notes of the tester.
     * @param   string          $sha          The tested commit.
     *
     * @return  void
     *
     * @since   1.0
     * @throws  \Exception
     */
    protected function submitOnGithub(TrackerProject $project, int $issueId, int $issueNumber, int $result, string $userComment, string $sha)
    {
        /** @var \JTracker\Application\Application $application */
        $application = $this->getContainer()->get('app');
        $user        = $application->getUser();
        $db          = $this->getContainer()->get('db');

        $table = (new IssuesTable($db))->load($issueId);
        $issue = IssueContext::fromTable($table, (new IssueModel($db))->getOpenClosed($table->status) === 'open');

        $service = TestResultServiceFactory::create($this->getContainer(), $project, $this->github);

        $data = new \stdClass();

        if ($result === TestResult::NOT_TESTED) {
            $service->withdraw($issue, $user->username);
        } else {
            $error = $service->validate($issue, $user->username, $result, $userComment);

            if ($error !== null) {
                throw new \Exception($error);
            }

            $comment = '/tested ' . ($result === TestResult::SUCCESS ? 'ok' : 'fail') . ($userComment !== '' ? "\r\n\r\n" . $userComment : '');
            $comment .= "\r\n\r\n<!-- jtracker-test-env " . json_encode(['sha' => $sha]) . ' -->';
            $comment .= (new GitHubHelper($this->getContainer()->get('gitHub')))->getApplicationComment($application, $project, $issueNumber);

            $data->comment = (new GitHubHelper($this->getContainer()->get('gitHub')))
                ->addComment($project, $issueNumber, $comment, $user->username, $db);

            $outcome = $service->record(
                $issue,
                $user->username,
                $result,
                $userComment,
                ['sha' => $sha],
                is_numeric($data->comment->comment_id) ? (int) $data->comment->comment_id : null,
                'tracker',
                $sha
            );

            if (!$outcome->accepted) {
                throw new \Exception($outcome->message);
            }
        }

        $summary = new TestSummary((new TestResultRepository($db))->getResults($issueId), $issue->headSha);

        $data->testResults = (object) [
            'testsSuccess' => $summary->getSuccessful(),
            'testsFailure' => $summary->getFailed(),
        ];

        $data->event = (object) [
            'activities_id' => 0,
            'event'         => 'test_item',
            'user'          => $user->username,
            'project_id'    => $project->project_id,
            'issue_number'  => $issueNumber,
            'created_date'  => (new \DateTime('now', new \DateTimeZone('UTC')))->format($db->getDateFormat()),
            'text'          => $result,
        ];

        $this->response->data = json_encode($data);

        $this->response->message = 'Test successfully added';
    }

    /**
     * Set the GitHub object with the credentials from the project or,
     * if not found, with those from the configuration file.
     *
     * @param   TrackerProject  $project  The Project object.
     *
     * @since   1.0
     * @return $this
     */
    protected function setProjectGitHubBot(TrackerProject $project)
    {
        // If there is a bot defined for the project, prefer it over the config credentials.
        $this->github = GithubFactory::getProjectBotInstance($this->getContainer()->get('app'), $project);

        return $this;
    }
}
