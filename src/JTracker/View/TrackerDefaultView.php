<?php
/**
 * @copyright  Copyright (C) 2013 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace JTracker\View;

use Joomla\Model\ModelInterface;

use JTracker\Model\TrackerDefaultModel;

/**
 * Class TrackerDefaultView.
 *
 * @since  1.0
 */
class TrackerDefaultView  extends AbstractTrackerHtmlView
{
	/**
	 * Method to instantiate the view.
	 *
	 * @param   ModelInterface  $model           The model object.
	 * @param   string|array    $templatesPaths  The templates paths.
	 *
	 * @since   1.0
	 */
	public function __construct(ModelInterface $model = null, $templatesPaths = '')
	{
		$model = $model ? : new TrackerDefaultModel;

		parent::__construct($model, $templatesPaths);
	}
}
