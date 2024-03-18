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
use Joomla\View\HtmlView;

/**
 * Abstract HTML view class for the Tracker application
 *
 * @since  1.0
 */
class BaseHtmlView extends HtmlView
{
    /**
     * The renderer object
     *
     * @var    StatefulModelInterface
     * @since  2.0.0
     */
    protected $model;

    /**
     * Method to instantiate the view.
     *
     * @param   StatefulModelInterface  $model     The model object.
     * @param   RendererInterface       $renderer  The renderer object.
     *
     * @since   2.0.0
     */
    public function __construct(StatefulModelInterface $model, RendererInterface $renderer)
    {
        parent::__construct($renderer);

        $this->model = $model;
    }
}
