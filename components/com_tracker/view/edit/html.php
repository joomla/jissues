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
	 * HTML Markup for a category list
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $categoryList;

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
	protected $fieldData;

	/**
	 * Object containing the list of fields
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $fieldList = array();

	/**
	 * HTML Markup for the fields
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $fields;

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
		$this->fieldData = $this->model->getFields($id);

		// Set up the editor object
		$this->editor = JEditor::getInstance('kisskontent');

		$this->editorParams = array(
			'preview-url'     => 'index.php?option=com_tracker&task=preview&format=raw',
			'syntaxpage-link' => 'index.php?option=com_tracker&view=markdowntestpage',
		);

		// Categories
		$section = 'com_tracker.' . $this->item->project_id . '.categories';
		$this->categoryList = JHtmlProjects::items($section)
			? JHtmlProjects::select($section, 'catid', $this->item->catid, 'N/A', '')
			: JHtmlProjects::select('com_tracker.categories', 'catid', $this->item->catid, 'N/A', '');

		// Fields
		$this->fields = JHtmlProjects::items('com_tracker.' . $this->item->project_id . '.fields');
		if (count($this->fields) == 0)
		{
			$this->fields = JHtmlProjects::items('com_tracker.fields');
		}

		foreach ($this->fields as $field)
		{
			// Project fields
			$this->fieldList[$field->alias] = JHtmlProjects::select(
				'com_tracker.' . $this->item->project_id . '.fields.' . $field->id,
				$field->id, $this->fieldData->get($field->id), 'N/A', ''
			);

			// Use global fields if no project fields are set.
			if ($this->fieldList[$field->alias] == '')
			{
				$this->fieldList[$field->alias] = JHtmlProjects::select(
					'com_tracker.fields.' . $field->id,
					$field->id, $this->fieldData->get($field->id), 'N/A', ''
				);
			}
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
		// Get the user object
		$user = JFactory::getUser();

		// Instantiate the JToolbar object
		$toolbar = JToolbar::getInstance('toolbar');

		// Add a button to save the item.
		$toolbar->appendButton('Standard', 'save', 'COM_TRACKER_TOOLBAR_SAVE', 'save', false);

		// Add a button to cancel editting the item.
		$toolbar->appendButton('Standard', 'cancel', 'COM_TRACKER_TOOLBAR_CANCEL', 'cancel', false);
	}
}
