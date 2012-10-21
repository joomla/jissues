<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Default controller class for the users component.
 *
 * @package     JTracker
 * @subpackage  com_users
 * @since       1.0
 */
class UsersControllerDefault extends JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 * @throws  RuntimeException if view class not found
	 */
	public function execute()
	{
		// Get the application
		/* @var JApplicationWeb $app */
		$app   = $this->getApplication();
		$input = $this->getInput();

		// Get the document object.
		$document = $app->getDocument();

		$vName   = $input->getWord('view', 'login');
		$vFormat = $document->getType();
		$lName   = $input->getWord('layout', 'default');

		$input->set('view', $vName);

		// Register the layout paths for the view
		$paths = new SplPriorityQueue;
		$paths->insert(JPATH_COMPONENT . '/view/' . $vName . '/tmpl', 'normal');

		$vClass = 'UsersView' . ucfirst($vName) . ucfirst($vFormat);
		$mClass = 'UsersModel' . ucfirst($vName);

		if (!class_exists($vClass))
		{
			throw new RuntimeException('View not found: ' . $vName);
		}

		/* @var JViewHtml $view */
		$view = new $vClass(new $mClass, $paths);
		$view->setLayout($lName);

		// Render our view.
		echo $view->render();

		return true;
	}
}
