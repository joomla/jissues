<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Default controller class for the Tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerDefault extends JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		// Get the application
		$app = $this->getApplication();

		// Get the document object.
		$document = $app->getDocument();

		$vName   = $app->input->getWord('view', 'issues');
		$vFormat = $document->getType();
		$lName   = $app->input->getWord('layout', 'default');

		$app->input->set('view', $vName);

		// Register the layout paths for the view
		$paths = new SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/view/' . $vName . '/tmpl', 'normal');

		$vClass = 'TrackerView' . ucfirst($vName) . ucfirst($vFormat);
		$mClass = 'TrackerModel' . ucfirst($vName);

		if (false == class_exists($mClass))
		{
			$mClass = 'TrackerModelDefault';
		}

		$view = new $vClass(new $mClass, $paths);
		$view->setLayout($lName);

		// Render our view.
		echo $view->render();

		return true;
	}
}
