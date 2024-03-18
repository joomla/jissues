<?php

/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View;

use Joomla\Model\StatefulModelInterface;
use Joomla\Renderer\RendererInterface;
use JTracker\Model\TrackerDefaultModel;

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
     * @param   StatefulModelInterface  $model     The model object.
     * @param   RendererInterface       $renderer  The renderer interface.
     *
     * @since   1.0
     */
    public function __construct(StatefulModelInterface $model = null, RendererInterface $renderer = null)
    {
        // We need a renderer but it has to be an optional argument unless we want to break a lot more stuff
        if (!$renderer) {
            throw new \InvalidArgumentException('Missing renderer');
        }

        $model = $model ?: new TrackerDefaultModel();

        parent::__construct($model, $renderer);
    }
}
