<?php
/**
 * @package    JTracker\View
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tracker\View;

use Joomla\Model\ModelInterface;
use Joomla\View\AbstractView;
use Joomla\Tracker\View\Renderer\TrackerExtension;
use Joomla\Tracker\View\Renderer\Twig;

/**
 * JTracker Html View class.
 *
 * @package  Joomla\Tracker\View
 */
abstract class AbstractTrackerHtmlView extends AbstractView
{
	/**
	 * The view layout.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $layout = 'index';

	/**
	 * The view template engine.
	 *
	 * @var    Twig
	 * @since  1.0
	 */
	protected $renderer = null;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   ModelInterface  $model           The model object.
	 * @param   string|array    $templatesPaths  The templates paths.
	 *
	 * @since   1.0
	 */
	public function __construct(ModelInterface $model, $templatesPaths = '')
	{
		parent::__construct($model);

		// Load the renderer.
		$config = array(
			'templates_base_dir' => JPATH_TEMPLATES
		);
		$this->renderer = new Twig($config);

		// Register tracker's Twig extension.
		$this->renderer->addExtension(new TrackerExtension());

		// Register additional paths.
		if (!empty($templatesPaths))
		{
			$this->renderer->setTemplatesPaths($templatesPaths, true);
		}
	}

	/**
	 * Magic toString method that is a proxy for the render method.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Method to escape output.
	 *
	 * @param   string  $output  The output to escape.
	 *
	 * @return  string  The escaped output.
	 *
	 * @see     ViewInterface::escape()
	 * @since   1.0
	 */
	public function escape($output)
	{
		// Escape the output.
		return htmlspecialchars($output, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Method to get the view layout.
	 *
	 * @return  string  The layout name.
	 *
	 * @since   1.0
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function render()
	{
		return $this->renderer->render($this->layout);
	}

	/**
	 * Method to set the view layout.
	 *
	 * @param   string  $layout  The layout name.
	 *
	 * @return  AbstractTrackerHtmlView  Method supports chaining.
	 *
	 * @since   1.0
	 */
	public function setLayout($layout)
	{
		$this->layout = $layout;

		return $this;
	}
}
