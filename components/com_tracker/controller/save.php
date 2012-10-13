<?php
/**
 * @package     X
 * @subpackage  X.Y
 *
 * @copyright   Copyright (C) 2012 X. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */


/**
 * Controller "save" class.
 *
 * @package  X
 *
 * @since    1.0
 */
class TrackerControllerSave extends JModelBase
{
	/**
	 * Execute the task.
	 *
	 * @return void
	 */
	public function execute()
	{
		$table = new TrackerTableIssues;

		$table->save(JFactory::getApplication()->input->post);

		JFactory::getApplication()->enqueueMessage('Table saved successfully', 'success');

		JFactory::getApplication()->redirect('index.php?option=com_tracker');
	}
}
