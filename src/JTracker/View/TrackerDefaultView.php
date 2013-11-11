<?php
/**
 * Part of the Joomla Tracker View Package
 *
 * @copyright  Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\View;

use Joomla\Model\ModelInterface;
use Joomla\View\Renderer\RendererInterface;
use Joomla\View\Renderer\Twig;

use JTracker\Model\TrackerDefaultModel;

/**
 * Default view class for the Tracker application
 *
 * @since  1.0
 */
class TrackerDefaultView  extends AbstractTrackerHtmlView
{
	/**
	 * Method to instantiate the view.
	 *
	 * @param   ModelInterface     $model           The model object.
	 * @param   RendererInterface  $renderer        The renderer interface.
	 * @param   string|array       $templatesPaths  The templates paths.
	 *
	 * @since   1.0
	 */
	public function __construct(ModelInterface $model = null, RendererInterface $renderer = null, $templatesPaths = '')
	{
		$model = $model ? : new TrackerDefaultModel;

		if (is_null($renderer))
		{
			$renderer = new Twig(
				array(
					'templates_base_dir' => JPATH_TEMPLATES,
					'environment' => array('debug' => true)
				)
			);
		}

		parent::__construct($model, $renderer, $templatesPaths);
	}
}
