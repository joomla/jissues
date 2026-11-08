<?php

/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Debug\Handler;

use App\Debug\DebugRedactor;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

/**
 * Whoops "pretty page" handler that is only used for users allowed to see debug output.
 *
 * For everybody else the handler is skipped, so the next handler on the stack (the production handler) takes over.
 * Sensitive values are hidden from the displayed superglobals.
 *
 * @since  1.0
 */
class RestrictedPrettyPageHandler extends PrettyPageHandler
{
    /**
     * Callback deciding if the current user may see the detailed error page.
     *
     * @var    callable
     * @since  1.0
     */
    private $isAllowed;

    /**
     * Constructor.
     *
     * @param   callable  $isAllowed  Callback deciding if the current user may see the detailed error page.
     *
     * @since   1.0
     */
    public function __construct(callable $isAllowed)
    {
        parent::__construct();

        $this->isAllowed = $isAllowed;
    }

    /**
     * Handle the error.
     *
     * @return  integer|null
     *
     * @since   1.0
     */
    public function handle()
    {
        try {
            $allowed = (bool) \call_user_func($this->isAllowed);
        } catch (\Throwable $throwable) {
            $allowed = false;
        }

        if (!$allowed) {
            return Handler::DONE;
        }

        $superGlobals = [
            '_SERVER' => $_SERVER,
            '_ENV'    => $_ENV,
            '_COOKIE' => $_COOKIE,
            '_POST'   => $_POST,
            '_GET'    => $_GET,
        ];

        foreach ($superGlobals as $superGlobal => $values) {
            foreach (array_keys($values) as $key) {
                if (DebugRedactor::isSensitiveKey($key)) {
                    $this->hideSuperglobalKey($superGlobal, $key);
                }
            }
        }

        return parent::handle();
    }
}
