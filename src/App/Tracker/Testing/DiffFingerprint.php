<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * Calculates a fingerprint of the diff of a pull request.
 *
 * The fingerprint only changes when the changes of the pull request itself change. A rebase onto, or a merge of,
 * the base branch moves the line numbers of the hunks, but keeps the fingerprint, so existing test results stay valid.
 *
 * @since  1.0
 */
class DiffFingerprint
{
    /**
     * Calculate the fingerprint from the files list of a pull request.
     *
     * @param   iterable  $files  The files as returned by the GitHub API (`GET /repos/{owner}/{repo}/pulls/{number}/files`).
     *
     * @return  string  A SHA1 hash.
     *
     * @since   1.0
     */
    public function calculate(iterable $files): string
    {
        $parts = [];

        foreach ($files as $file) {
            $file = (object) $file;

            $filename = (string) ($file->filename ?? '');

            $parts[$filename] = implode(
                "\0",
                [
                    $filename,
                    (string) ($file->previous_filename ?? ''),
                    (string) ($file->status ?? ''),
                    $this->normalisePatch($file),
                ]
            );
        }

        ksort($parts, SORT_STRING);

        return sha1(implode("\0\0", $parts));
    }

    /**
     * Normalise the patch of a file.
     *
     * The line numbers in the hunk headers are removed, they change whenever the base branch changes.
     * Files without a patch (binary files or very large diffs) are represented by the blob SHA of the file.
     *
     * @param   object  $file  The file data.
     *
     * @return  string
     *
     * @since   1.0
     */
    private function normalisePatch(object $file): string
    {
        if (!isset($file->patch) || $file->patch === '') {
            return 'blob:' . ($file->sha ?? '');
        }

        $patch = str_replace(["\r\n", "\r"], "\n", (string) $file->patch);

        return (string) preg_replace('/^@@ -\d+(?:,\d+)? \+\d+(?:,\d+)? @@.*$/m', '@@', $patch);
    }
}
