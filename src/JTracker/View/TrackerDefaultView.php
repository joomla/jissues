<?php

/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View;

use Joomla\Model\StatefulModelInterface;
use JTracker\Model\TrackerDefaultModel;
use Twig\Environment;

/**
 * Default view class for the Tracker application
 *
 * @since  1.0
 */
class TrackerDefaultView extends AbstractTrackerHtmlView
{
    /**
     * Method to instantiate the view.
     *
     * @param   StatefulModelInterface  $model  The model object.
     * @param   Environment             $twig   The Twig environment.
     *
     * @since   1.0
     */
    public function __construct(?StatefulModelInterface $model = null, ?Environment $twig = null)
    {
        // We need a Twig environment but it has to be an optional argument unless we want to break a lot more stuff
        if (!$twig) {
            throw new \InvalidArgumentException('Missing Twig environment');
        }

        $model = $model ?: new TrackerDefaultModel();

        parent::__construct($model, $twig);
    }
}
