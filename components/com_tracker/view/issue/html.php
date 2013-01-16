<?php
/**
 * @package     JTracker
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * The issues detail view
 *
 * @package     JTracker
 * @subpackage  View
 * @since       1.0
 */
class TrackerViewIssueHtml extends JViewHtml
{
	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var    TrackerModelIssue
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Object containing the additional field data
	 *
	 * @var    JRegistry
	 * @since  1.0
	 */
	protected $fieldsData = array();

	/**
	 * @var stdClass
	 * @since  1.0
	 */
	protected $item;

	/**
	 * @var JTrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * @var array
	 * @since  1.0
	 */
	protected $activity;

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

		$id         = $app->input->getInt('id', 1);
		$this->item = $this->model->getItem($id);

		if (!$this->item)
		{
			// We expect an error message in the message queue..
			return '';
		}

		$this->project = new JTrackerProject($this->item->project_id);

		$this->fieldsData = $this->model->getFieldsData($id);
		$this->activity = $this->model->getActivity($id);

		// Build the toolbar
		$this->buildToolbar();

		return parent::render();
	}

	/**
	 * Method to build the view's toolbar
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function buildToolbar()
	{
		// Get the user object
		$user = JFactory::getUser();

		// Instantiate the JToolbar object
		$toolbar = JToolbar::getInstance('toolbar');

		// Add a button to edit the item.
		if ($user->authorise('core.edit', 'com_tracker.issue.' . $this->item->id))
		{
			$toolbar->appendButton('Standard', 'edit', 'COM_TRACKER_TOOLBAR_EDIT', 'edit', false);
		}
	}
}
