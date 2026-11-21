<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Testing;

/**
 * The outcome of a test command.
 *
 * @since  1.0
 */
final class TestOutcome
{
    /**
     * Constructor.
     *
     * @param   boolean          $accepted  Whether the command has been executed.
     * @param   string           $message   The reason if the command has been rejected.
     * @param   TestResult|null  $result    The recorded test result.
     *
     * @since   1.0
     */
    public function __construct(
        public readonly bool $accepted,
        public readonly string $message = '',
        public readonly ?TestResult $result = null
    ) {
    }

    /**
     * Create an outcome for an executed command.
     *
     * @param   TestResult|null  $result  The recorded test result.
     *
     * @return  self
     *
     * @since   1.0
     */
    public static function accepted(?TestResult $result = null): self
    {
        return new self(true, '', $result);
    }

    /**
     * Create an outcome for a rejected command.
     *
     * @param   string  $message  The reason.
     *
     * @return  self
     *
     * @since   1.0
     */
    public static function rejected(string $message): self
    {
        return new self(false, $message);
    }
}
