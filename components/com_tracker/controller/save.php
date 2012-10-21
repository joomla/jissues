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
 * Controller class to save an item via the tracker component.
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerControllerSave extends JControllerBase
{
	/**
	 * Execute the task.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$table = new JTableIssue;

		$table->save($this->input->post);

		$this->app->enqueueMessage('Table saved successfully', 'success');

		$this->app->redirect('index.php?option=com_tracker');
	}
}
