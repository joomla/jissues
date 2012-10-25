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
		$this->item     = $this->model->getItem($id);
		$this->comments = $this->model->getComments($id);
		$this->fields   = $this->model->getFields($id);

		$dispatcher	= JEventDispatcher::getInstance();

		$o = new stdClass;
		$o->text = $this->item->description;

		$params = new JRegistry;

		JPluginHelper::importPlugin('content');
		$dispatcher->trigger('onContentPrepare', array ('com_tracker.markdown', &$o, $params));

		$this->item->description_raw = $this->item->description;
		$this->item->description = $o->text;

		foreach ($this->comments as &$comment)
		{
			// @todo Maybe we should parse the comments on retrieval and write the result to the database
			$dispatcher->trigger('onContentPrepare', array ('com_tracker.markdown', &$comment, $params));
		}

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
		$toolbar->appendButton('Standard', 'edit', 'COM_TRACKER_TOOLBAR_EDIT', 'edit', false);
	}
}
