<?php

/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2015 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Helper;

use App\Projects\TrackerProject;
use App\Tracker\Model\ActivityModel;
use Joomla\Database\DatabaseInterface;
use Joomla\Http\Exception\UnexpectedResponseException;
use JTracker\Application\Application;
use JTracker\Github\DataType\Commit;
use JTracker\Github\DataType\Commit\CombinedStatus;
use JTracker\Github\DataType\Commit\Status;
use JTracker\Github\DataType\JTracker\Issues\Comment;
use JTracker\Github\Github;

/**
 * Helper class containing methods for working with GitHub.
 *
 * @since  1.0
 */
class GitHubHelper
{
    /**
     * @var Github
     */
    private $gitHub;

    /**
     * Constructor.
     *
     * @param   Github  $gitHub  The GitHub object.
     */
    public function __construct($gitHub)
    {
        $this->gitHub = $gitHub;
    }

    /**
     * Add a comment on GitHub.
     *
     * @param   TrackerProject     $project      The project.
     * @param   integer            $issueNumber  The issue number.
     * @param   string             $comment      The comment to add.
     * @param   string             $userName     The username.
     * @param   DatabaseInterface  $database     The database driver object.
     *
     * @return  Comment  The GitHub comment object
     *
     * @throws \DomainException
     *
     * @since  1.0
     */
    public function addComment(TrackerProject $project, $issueNumber, $comment, $userName, DatabaseInterface $database)
    {
        $data = new Comment();

        if ($project->gh_user && $project->gh_project) {
            $gitHubResponse = $this->gitHub->issues->comments->create(
                $project->gh_user,
                $project->gh_project,
                $issueNumber,
                $comment
            );

            if (!isset($gitHubResponse->id)) {
                throw new \DomainException('Invalid response from GitHub');
            }

            $data->created_at = $gitHubResponse->created_at;
            $data->opened_by  = $gitHubResponse->user->login;
            $data->comment_id = $gitHubResponse->id;
            $data->text_raw   = $gitHubResponse->body;

            $data->text = $this->gitHub->markdown->render(
                $comment,
                'gfm',
                $project->gh_user . '/' . $project->gh_project
            );
        } else {
            $data->created_at = (new \DateTime())->format($database->getDateFormat());
            $data->opened_by  = $userName;
            $data->comment_id = '???';
            $data->text_raw   = $comment;
            $data->text       = $this->gitHub->markdown->render($comment, 'markdown');
        }

        (new ActivityModel($database))
            ->addActivityEvent(
                'comment',
                $data->created_at,
                $data->opened_by,
                $project->project_id,
                $issueNumber,
                $data->comment_id,
                $data->text,
                $data->text_raw
            );

        $data->activities_id = $database->insertid();

        $data->created_at = (new \DateTime($data->created_at))->format('j M Y');

        return $data;
    }

    /**
     * Get the commits for a GitHub pull request.
     *
     * @param   TrackerProject  $project      The project object.
     * @param   integer         $issueNumber  The issue number.
     *
     * @return  Commit[]
     *
     * @since   1.0
     */
    public function getCommits(TrackerProject $project, $issueNumber)
    {
        $commits = [];

        $commitData = $this->gitHub->pulls->getCommits(
            $project->gh_user,
            $project->gh_project,
            $issueNumber
        );

        foreach ($commitData as $commit) {
            $c = new Commit();

            $c->sha            = $commit->sha;
            $c->message        = $commit->commit->message;
            $c->author_name    = $commit->author->login ?? '';
            $c->author_date    = $commit->commit->author->date;
            $c->committer_name = $commit->committer->login ?? '';
            $c->committer_date = $commit->commit->committer->date;

            $commits[] = $c;
        }

        return $commits;
    }

    /**
     * Get the combined GitHub merge status for an issue.
     *
     * Combines the commit statuses (e.g. from external CI services) with the check runs, which is how GitHub Actions
     * and other GitHub Apps report their results.
     *
     * @param   TrackerProject  $project  The project object.
     * @param   string          $ref      Ref to fetch the status for. It can be a SHA, a branch name, or a tag name.
     *
     * @return  CombinedStatus
     *
     * @since    1.0
     */
    public function getCombinedStatus(TrackerProject $project, $ref)
    {
        $combinedStatus = new CombinedStatus();

        $combined = $this->gitHub->repositories->statuses->getCombined(
            $project->gh_user,
            $project->gh_project,
            $ref
        );

        $combinedStatus->state = $combined->state;

        foreach ($combined->statuses as $status) {
            $s = new Status();

            $s->state       = $status->state;
            $s->targetUrl   = $status->target_url;
            $s->description = $status->description;
            $s->context     = $status->context;

            $combinedStatus->statuses[] = $s;
        }

        $checkRuns = $this->getCheckRuns($project, $ref);

        foreach ($checkRuns as $checkRun) {
            $s = new Status();

            $s->state       = self::getCheckRunState($checkRun);
            $s->targetUrl   = $checkRun->html_url ?? '';
            $s->description = self::getCheckRunDescription($checkRun);
            $s->context     = $checkRun->name ?? '';

            $combinedStatus->statuses[] = $s;
        }

        // GitHub reports "pending" for a commit without commit statuses, so the state is recomputed when there are check runs
        if ($checkRuns) {
            $combinedStatus->state = self::combineStates(array_column($combinedStatus->statuses, 'state'));
        }

        return $combinedStatus;
    }

    /**
     * Get the check runs of a ref.
     *
     * @param   TrackerProject  $project  The project object.
     * @param   string          $ref      Ref to fetch the check runs for.
     *
     * @return  array  The check runs, an empty array if GitHub doesn't return any.
     *
     * @since   1.0
     */
    private function getCheckRuns(TrackerProject $project, $ref): array
    {
        try {
            $result = $this->gitHub->repositories->statuses->getCheckRuns($project->gh_user, $project->gh_project, $ref);
        } catch (UnexpectedResponseException $exception) {
            // Without access to the checks API, show the commit statuses only, as before
            return [];
        }

        return isset($result->check_runs) && \is_array($result->check_runs) ? $result->check_runs : [];
    }

    /**
     * Map a check run to the state of a commit status.
     *
     * @param   object  $checkRun  The check run.
     *
     * @return  string  One of pending, success or failure.
     *
     * @since   1.0
     */
    private static function getCheckRunState($checkRun): string
    {
        if (($checkRun->status ?? '') !== 'completed') {
            return 'pending';
        }

        switch ($checkRun->conclusion ?? '') {
            case 'success':
            case 'neutral':
            case 'skipped':
                return 'success';

            case 'stale':
                return 'pending';

            default:
                // failure, cancelled, timed_out, action_required, startup_failure
                return 'failure';
        }
    }

    /**
     * Get a short description of a check run, like the description of a commit status.
     *
     * @param   object  $checkRun  The check run.
     *
     * @return  string
     *
     * @since   1.0
     */
    private static function getCheckRunDescription($checkRun): string
    {
        if (!empty($checkRun->output->title)) {
            return (string) $checkRun->output->title;
        }

        $state = ($checkRun->status ?? '') === 'completed' ? ($checkRun->conclusion ?? '') : ($checkRun->status ?? '');

        return ucfirst(str_replace('_', ' ', (string) $state));
    }

    /**
     * Combine the states of several statuses into one state, the way GitHub combines commit statuses.
     *
     * @param   string[]  $states  The states.
     *
     * @return  string  failure if any status failed or errored, else pending if any is pending, else success.
     *
     * @since   1.0
     */
    private static function combineStates(array $states): string
    {
        if (array_intersect($states, ['failure', 'error'])) {
            return 'failure';
        }

        if (\in_array('pending', $states, true)) {
            return 'pending';
        }

        return 'success';
    }

    /**
     * Create a GitHub merge status for the last commit in a PR.
     *
     * @param   TrackerProject  $project      The project object.
     * @param   integer         $issueNumber  The issue number.
     * @param   string          $state        The state (pending, success, error or failure).
     * @param   string          $targetUrl    Optional target URL.
     * @param   string          $description  Optional description for the status.
     * @param   string          $context      A string label to differentiate this status from the status of other systems.
     * @param   string          $sha          The SHA for the commit.
     *
     * @return  Status
     *
     * @since   1.0
     */
    public function createStatus(TrackerProject $project, $issueNumber, $state, $targetUrl, $description, $context, $sha = '')
    {
        if (!$sha) {
            $pullRequest = $this->gitHub->pulls->get(
                $project->gh_user,
                $project->gh_project,
                $issueNumber
            );

            $sha = $pullRequest->head->sha;
        }

        return $this->gitHub->repositories->statuses->create(
            $project->gh_user,
            $project->gh_project,
            $sha,
            $state,
            $targetUrl,
            $description,
            $context
        );
    }

    /**
     * Get a string for usage in comment footers.
     *
     * @param   Application     $application  The application object.
     * @param   TrackerProject  $project      The project object.
     * @param   integer         $issueNumber  The issue number.
     *
     * @since   1.0
     * @return string
     */
    public function getApplicationComment(Application $application, TrackerProject $project, $issueNumber)
    {
        return \sprintf(
            '<hr /><sub>This comment was created with the <a href="%1$s">%2$s Application</a> at <a href="%3$s">%4$s</a>.</sub>',
            'https://github.com/joomla/jissues',
            'J!Tracker',
            $application->get('uri')->base->full . 'tracker/' . $project->alias . '/' . $issueNumber,
            str_replace(['http://', 'https://'], '', $application->get('uri')->base->full) . 'tracker/' . $project->alias . '/' . $issueNumber
        );
    }
}
