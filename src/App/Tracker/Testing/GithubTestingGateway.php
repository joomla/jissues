<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

use App\Projects\TrackerProject;
use JTracker\Github\Github;

/**
 * The GitHub API calls needed for testing on GitHub.
 *
 * @since  1.0
 */
class GithubTestingGateway
{
    /**
     * Number of files per page when fetching the files of a pull request.
     *
     * @var    integer
     * @since  1.0
     */
    private const FILES_PER_PAGE = 100;

    /**
     * GitHub returns at most 3000 files for a pull request.
     *
     * @var    integer
     * @since  1.0
     */
    private const MAX_FILE_PAGES = 30;

    /**
     * Constructor.
     *
     * @param   Github          $github   The GitHub object, authenticated as the editbot.
     * @param   TrackerProject  $project  The project.
     *
     * @since   1.0
     */
    public function __construct(private readonly Github $github, private readonly TrackerProject $project)
    {
    }

    /**
     * Get a pull request.
     *
     * @param   integer  $number  The pull request number.
     *
     * @return  object
     *
     * @since   1.0
     */
    public function getPullRequest(int $number): object
    {
        return $this->github->pulls->get($this->project->gh_user, $this->project->gh_project, $number);
    }

    /**
     * Get all files of a pull request.
     *
     * @param   integer  $number  The pull request number.
     *
     * @return  object[]
     *
     * @since   1.0
     */
    public function getPullRequestFiles(int $number): array
    {
        $files = [];
        $page  = 1;

        do {
            $count = 0;

            foreach ($this->github->pulls->getFiles($this->project->gh_user, $this->project->gh_project, $number, $page, self::FILES_PER_PAGE) as $file) {
                $files[] = $file;
                $count++;
            }

            $page++;
        } while ($count === self::FILES_PER_PAGE && $page <= self::MAX_FILE_PAGES);

        return $files;
    }

    /**
     * Update the description of a pull request.
     *
     * @param   integer  $number  The pull request number.
     * @param   string   $body    The new description.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function updatePullRequestBody(int $number, string $body): void
    {
        $this->github->pulls->edit($this->project->gh_user, $this->project->gh_project, $number, null, $body);
    }

    /**
     * Add labels to a pull request.
     *
     * @param   integer   $number  The pull request number.
     * @param   string[]  $labels  The label names.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function addLabels(int $number, array $labels): void
    {
        $this->github->issues->labels->add($this->project->gh_user, $this->project->gh_project, (string) $number, $labels);
    }

    /**
     * Remove a label from a pull request.
     *
     * @param   integer  $number  The pull request number.
     * @param   string   $label   The label name.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function removeLabel(int $number, string $label): void
    {
        $this->github->issues->labels->removeFromIssue($this->project->gh_user, $this->project->gh_project, (string) $number, $label);
    }

    /**
     * Create a commit status.
     *
     * @param   string  $sha          The commit SHA.
     * @param   string  $state        The state (pending, success, error or failure).
     * @param   string  $targetUrl    The target URL.
     * @param   string  $description  The description.
     * @param   string  $context      The context.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function createStatus(string $sha, string $state, string $targetUrl, string $description, string $context): void
    {
        $this->github->repositories->statuses->create(
            $this->project->gh_user,
            $this->project->gh_project,
            $sha,
            $state,
            $targetUrl,
            $description,
            $context
        );
    }

    /**
     * Add a reaction to a comment.
     *
     * @param   integer  $commentId  The comment ID.
     * @param   string   $content    The reaction (e.g. +1 or confused).
     *
     * @return  void
     *
     * @since   1.0
     */
    public function addReaction(int $commentId, string $content): void
    {
        $this->github->issues->comments->createReaction($this->project->gh_user, $this->project->gh_project, $commentId, $content);
    }

    /**
     * Create a comment.
     *
     * @param   integer  $number  The pull request number.
     * @param   string   $body    The comment text.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function createComment(int $number, string $body): void
    {
        $this->github->issues->comments->create($this->project->gh_user, $this->project->gh_project, $number, $body);
    }

    /**
     * Get the permission level of a user for the repository.
     *
     * @param   string  $login  The GitHub login.
     *
     * @return  string  One of admin, maintain, write, triage, read or none.
     *
     * @since   1.0
     */
    public function getPermission(string $login): string
    {
        return $this->github->repositories->collaborators->getPermission($this->project->gh_user, $this->project->gh_project, $login);
    }
}
