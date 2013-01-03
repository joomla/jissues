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
 * Controller class to preview an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerPreview extends JControllerBase
{
	/**
	 * Execute the controller.
	 *
	 * @return  boolean  True if controller finished execution, false if the controller did not
	 *                   finish execution. A controller might return false if some precondition for
	 *                   the controller to run has not been satisfied.
	 *
	 * @since   1.0
	 * @throws  LogicException
	 * @throws  RuntimeException
	 */
	public function execute()
	{
		$text = $this->input->getHtml('text', '');

		// Check if we actually have something to parse
		if (!$text)
		{
			echo 'Nothing to preview...';
			$this->app->close();
		}

		// Instantiate JGithub
		$github = new JGithub;

		// Parse the text
		$text = $github->markdown->render($text, 'gfm', 'JTracker/jissues');

		// Echo out the text for
		echo $text ? : 'Nothing to preview...';

		$this->app->close();
	}
}
