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
 * The issues list view
 *
 * @package     BabDev.Tracker
 * @subpackage  View
 * @since       1.0
 */
class TrackerViewIssuesHtml extends JViewHtml
{
	/**
	 * Container for the view's items
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $items;

	/**
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     TrackerModelIssues
	 * @since   1.0
	 */
	protected $model;

	/**
	 * Pagination object
	 *
	 * @var    JPagination
	 * @since  1.0
	 */
	protected $pagination;

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

		$this->items      = $this->model->getItems();
		$this->pagination = $this->model->getPagination();
		$this->state      = $this->model->getState();

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
		$toolbar = JToolbar::getInstance('toolbar');

		// Add a button to submit a new item.
		$toolbar->appendButton('Standard', 'new', 'COM_TRACKER_TOOLBAR_ADD', 'add', false);
	}
}
