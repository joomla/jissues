<?php

/**
 * Part of the Joomla Tracker Twig Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Twig\Service;

use JTracker\Security\CsrfTokenManager;

/**
 * Service class rendering the hidden form field carrying the CSRF token
 *
 * @since  __DEPLOY_VERSION__
 */
class CsrfFieldRenderer
{
    /**
     * CSRF token manager
     *
     * @var    CsrfTokenManager
     * @since  __DEPLOY_VERSION__
     */
    private $tokenManager;

    /**
     * Constructor.
     *
     * @param   CsrfTokenManager  $tokenManager  CSRF token manager
     *
     * @since   __DEPLOY_VERSION__
     */
    public function __construct(CsrfTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Render the hidden form field carrying the CSRF token
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    public function render(): string
    {
        return \sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            CsrfTokenManager::FORM_FIELD,
            htmlspecialchars($this->tokenManager->getToken(), ENT_QUOTES, 'UTF-8')
        );
    }
}
