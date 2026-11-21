<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Testing;

use App\Tracker\Testing\DiffFingerprint;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the diff fingerprint.
 *
 * @since  1.0
 */
class DiffFingerprintTest extends TestCase
{
    /**
     * Create a file entry like the GitHub API returns it.
     *
     * @param   string       $filename  The file name.
     * @param   string|null  $patch     The patch.
     * @param   string       $sha       The blob SHA.
     * @param   string       $status    The status.
     *
     * @return  object
     */
    private function file(string $filename, ?string $patch, string $sha = 'blob1', string $status = 'modified'): object
    {
        $file = (object) ['filename' => $filename, 'status' => $status, 'sha' => $sha];

        if ($patch !== null) {
            $file->patch = $patch;
        }

        return $file;
    }

    /**
     * A rebase moves the hunks and changes the blob SHA, but keeps the fingerprint.
     *
     * @return  void
     */
    public function testRebaseKeepsFingerprint(): void
    {
        $fingerprint = new DiffFingerprint();

        $before = $fingerprint->calculate([
            $this->file('a.php', "@@ -10,3 +10,3 @@ function a()\n context\n-old\n+new\n", 'blob1'),
            $this->file('b.php', "@@ -1 +1 @@\n-x\n+y\n", 'blob2'),
        ]);

        $after = $fingerprint->calculate([
            // Other order, other line numbers, other hunk context and other blob SHAs
            $this->file('b.php', "@@ -1 +1 @@\r\n-x\r\n+y\r\n", 'blob4'),
            $this->file('a.php', "@@ -42,3 +42,3 @@ function b()\n context\n-old\n+new\n", 'blob3'),
        ]);

        $this->assertSame($before, $after);
    }

    /**
     * A changed line changes the fingerprint.
     *
     * @return  void
     */
    public function testChangedLineChangesFingerprint(): void
    {
        $fingerprint = new DiffFingerprint();

        $this->assertNotSame(
            $fingerprint->calculate([$this->file('a.php', "@@ -10,3 +10,3 @@\n-old\n+new\n")]),
            $fingerprint->calculate([$this->file('a.php', "@@ -10,3 +10,3 @@\n-old\n+newer\n")])
        );
    }

    /**
     * An added file changes the fingerprint.
     *
     * @return  void
     */
    public function testAddedFileChangesFingerprint(): void
    {
        $fingerprint = new DiffFingerprint();

        $this->assertNotSame(
            $fingerprint->calculate([$this->file('a.php', "@@ -1 +1 @@\n-a\n+b\n")]),
            $fingerprint->calculate([$this->file('a.php', "@@ -1 +1 @@\n-a\n+b\n"), $this->file('c.php', "@@ -0,0 +1 @@\n+c\n", 'blob', 'added')])
        );
    }

    /**
     * Files without a patch are compared by their blob SHA.
     *
     * @return  void
     */
    public function testBinaryFilesUseBlobSha(): void
    {
        $fingerprint = new DiffFingerprint();

        $this->assertSame(
            $fingerprint->calculate([$this->file('logo.png', null, 'blob1', 'added')]),
            $fingerprint->calculate([$this->file('logo.png', null, 'blob1', 'added')])
        );

        $this->assertNotSame(
            $fingerprint->calculate([$this->file('logo.png', null, 'blob1', 'added')]),
            $fingerprint->calculate([$this->file('logo.png', null, 'blob2', 'added')])
        );
    }
}
