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
	 * @var    TrackerModelEdit
	 * @since  1.0
	 */
	protected $model;

	/**
	 * Container for the JEditor object
	 *
	 * @var    JEditor
	 * @since  1.0
	 */
	protected $editor;

	/**
	 * Array containing the editor params
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $editorParams = array();

	/**
	 * Object containing the additional field data
	 *
	 * @var    JRegistry
	 * @since  1.0
	 */
	protected $fieldsData;

	protected $item;

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
		$id         = JFactory::getApplication()->input->getInt('id', 0);
		$this->item = $this->model->getItem($id);

		if (!$this->item)
		{
			throw new RuntimeException('Invalid item');
		}

		$this->fieldsData = $this->model->getFieldsData($id);

		// Set up the editor object
		$this->editor = JEditor::getInstance('kisskontent');

		$this->editorParams = array(
			'preview-url'     => 'index.php?option=com_tracker&task=preview',
			'syntaxpage-link' => 'index.php?option=com_tracker&view=markdowntestpage',
		);

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
		// Instantiate the JToolbar object
		$toolbar = JToolbar::getInstance('toolbar');

		// Add a button to save the item.
		$toolbar->appendButton('Standard', 'save', 'COM_TRACKER_TOOLBAR_SAVE', 'save', false);

		// Add a button to cancel editting the item.
		$toolbar->appendButton('Standard', 'cancel', 'COM_TRACKER_TOOLBAR_CANCEL', 'cancel', false);
	}
}
