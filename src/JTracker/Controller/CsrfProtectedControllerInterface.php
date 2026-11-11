<?php

/**
 * Part of the Joomla Tracker Controller Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Controller;

/**
 * Marker interface for controllers performing state changing actions.
 *
 * The application only executes controllers implementing this interface for POST requests
 * carrying a valid CSRF token (see \JTracker\Security\CsrfTokenManager).
 *
 * @since  __DEPLOY_VERSION__
 */
interface CsrfProtectedControllerInterface
{
}
