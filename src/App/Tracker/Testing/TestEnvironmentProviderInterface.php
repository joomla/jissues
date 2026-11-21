<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

use App\Projects\TrackerProject;

/**
 * Provides the link to a prepared test environment for a pull request.
 *
 * Register an implementation as `App\Tracker\Testing\TestEnvironmentProviderInterface` in the DI container
 * to show the link in the test section of the pull request description.
 *
 * @since  1.0
 */
interface TestEnvironmentProviderInterface
{
    /**
     * Get the URL of the test environment for a pull request.
     *
     * @param   TrackerProject  $project      The project.
     * @param   integer         $issueNumber  The pull request number.
     * @param   string|null     $headSha      The head commit of the pull request.
     *
     * @return  string|null  The URL, or null if no test environment is available.
     *
     * @since   1.0
     */
    public function getUrl(TrackerProject $project, int $issueNumber, ?string $headSha): ?string;
}
