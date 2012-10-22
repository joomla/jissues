<?php
/**
 * @package     JTracker
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * The issues edit view
 *
 * @package     JTracker
 * @subpackage  View
 * @since       1.0
 */
class TrackerViewEditHtml extends JViewHtml
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
	protected $fields = array();

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

		$id = $app->input->getInt('id', 1);
		$this->item   = $this->model->getItem($id);
		$this->fields = $this->item->fields;

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
	}
}
