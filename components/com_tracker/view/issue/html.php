<?php
/**
 * @package     BabDev.Tracker
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2012 Michael Babker. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;


/**
 * The issues detail view
 *
 * @package     BabDev.Tracker
 * @subpackage  View
 * @since       1.0
 */

class TrackerViewIssueHtml extends JViewHtml
{
	/**
	 * Method to render the view.
	 *
	 * @return  string  The rendered view.
	 *
	 * @since   1.0
	 * @throws  RuntimeException
	 */
	public function render()
	{
		$app = JFactory::getApplication();

		// Register the document
		$this->document = $app->getDocument();
		
		$id = JRequest::getVar('id', 1); // update this to the proper get cmd
		
		$this->item = $this->model->getItem($id);

		return parent::render();
	}
}