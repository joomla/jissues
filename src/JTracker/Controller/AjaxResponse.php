<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

/**
 * AJAX response object
 *
 * @since  1.0
 */
class AjaxResponse
{
    /**
     * Data object.
     *
     * @var    mixed
     * @since  1.0
     */
    public $data;

    /**
     * Error message.
     *
     * @var    string
     * @since  1.0
     */
    public $error = '';

    /**
     * Message string.
     *
     * @var    string
     * @since  1.0
     */
    public $message = '';

    /**
     * Link to edit the shown documentation page (documentation view only).
     *
     * @var    string|null
     * @since  1.0
     */
    public $editLink;

    /**
     * Permanent link to the shown documentation page (documentation view only).
     *
     * @var    string|null
     * @since  1.0
     */
    public $permaLink;

    /**
     * Information about uploaded files (upload controller only).
     *
     * @var    array|null
     * @since  1.0
     */
    public $files;

    /**
     * Constructor
     *
     * @since  1.0
     */
    public function __construct()
    {
        $this->data = new \stdClass();
    }
}
