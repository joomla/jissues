<?php

/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View;

use Joomla\Model\StatefulModelInterface;
use Twig\Environment;

/**
 * HTML view class for the Tracker application which renders a Twig template
 *
 * @since  1.0
 */
class BaseHtmlView
{
    /**
     * The model object
     *
     * @var    StatefulModelInterface
     * @since  2.0.0
     */
    protected $model;

    /**
     * The Twig environment
     *
     * @var    Environment
     * @since  1.0
     */
    protected $twig;

    /**
     * The view layout, the name of the Twig template to render.
     *
     * @var    string
     * @since  2.0.0
     */
    protected $layout = 'default';

    /**
     * The data to pass to the template.
     *
     * @var    array
     * @since  2.0.0
     */
    private $data = [];

    /**
     * Method to instantiate the view.
     *
     * @param   StatefulModelInterface  $model  The model object.
     * @param   Environment             $twig   The Twig environment.
     *
     * @since   2.0.0
     */
    public function __construct(StatefulModelInterface $model, Environment $twig)
    {
        $this->model = $model;
        $this->twig  = $twig;
    }

    /**
     * Magic toString method that is a proxy for the render method.
     *
     * @return  string
     *
     * @since   2.0.0
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Add a variable to the template data.
     *
     * @param   string  $key    The variable name.
     * @param   mixed   $value  The variable value.
     *
     * @return  $this
     *
     * @since   2.0.0
     */
    public function addData(string $key, $value)
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Reset the template data.
     *
     * @return  $this
     *
     * @since   2.0.0
     */
    public function clearData()
    {
        $this->data = [];

        return $this;
    }

    /**
     * Get the template data.
     *
     * @return  array
     *
     * @since   2.0.0
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Remove a variable from the template data.
     *
     * @param   string  $key  The variable name.
     *
     * @return  $this
     *
     * @since   2.0.0
     */
    public function removeData(string $key)
    {
        unset($this->data[$key]);

        return $this;
    }

    /**
     * Replace the template data.
     *
     * @param   array  $data  The template data.
     *
     * @return  $this
     *
     * @since   2.0.0
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Method to get the view layout.
     *
     * @return  string  The layout name.
     *
     * @since   2.0.0
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * Method to set the view layout.
     *
     * @param   string  $layout  The layout name.
     *
     * @return  $this
     *
     * @since   2.0.0
     */
    public function setLayout(string $layout)
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get the Twig environment.
     *
     * @return  Environment
     *
     * @since   1.0
     */
    public function getTwig(): Environment
    {
        return $this->twig;
    }

    /**
     * Method to render the view.
     *
     * @return  string  The rendered view.
     *
     * @since   2.0.0
     */
    public function render()
    {
        return $this->twig->render($this->getLayout(), $this->getData());
    }
}
