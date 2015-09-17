<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\View;

use App\Projects\ProjectAwareTrait;

use Joomla\Model\ModelInterface;
use Joomla\View\AbstractView;
use Joomla\View\Renderer\RendererInterface;

/**
 * Abstract HTML view class for the Tracker application
 *
 * @since  1.0
 */
abstract class AbstractTrackerHtmlView extends AbstractView
{
	use ProjectAwareTrait;

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
	 * @var    RendererInterface
	 * @since  1.0
	 */
	protected $renderer = null;

	/**
	 * Method to instantiate the view.
	 *
	 * @param   ModelInterface     $model     The model object.
	 * @param   RendererInterface  $renderer  The renderer object.
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	public function __construct(ModelInterface $model, RendererInterface $renderer)
	{
		parent::__construct($model);

		$this->renderer = $renderer;
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
	 * Method to get the renderer object.
	 *
	 * @return  RendererInterface  The renderer object.
	 *
	 * @since   1.0
	 */
	public function getRenderer()
	{
		return $this->renderer;
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
	 * @return  $this  Method supports chaining
	 *
	 * @since   1.0
	 */
	public function setLayout($layout)
	{
		$this->layout = $layout;

		return $this;
	}
}
