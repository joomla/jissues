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
	 * Redefine the model so the correct type hinting is available.
	 *
	 * @var     TrackerModelIssue
	 * @since   1.0
	 */
	protected $model;

	protected $fields = array();

	protected $fieldList = array();

	protected $categoryList = '';

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

		$this->item = $this->model->getItem($id);
		$this->comments = $this->model->getComments($id);

		// Categories

		$section = 'com_tracker.' . $this->item->project_id . '.categories';

		$this->categoryList = JHtmlProjects::items($section)
			? JHtmlProjects::select($section, 'catid', $this->item->catid, 'N/A', '')
			: JHtmlProjects::select('com_tracker.categories', 'catid', $this->item->catid, 'N/A', '');

		// Fields

		$this->fields = JHtmlProjects::items('com_tracker.' . $this->item->project_id . '.fields');

		if (0 == count($this->fields))
		{
			$this->fields = JHtmlProjects::items('com_tracker.fields');
		}

		foreach ($this->fields as $field)
		{
			// Project fields
			$this->fieldList[$field->alias] = JHtmlProjects::select(
				'com_tracker.' . $this->item->project_id . '.fields.' . $field->id,
				$field->id, $this->item->fields->get($field->id), 'N/A', ''
			);

			// Use global fields if no project fields are set.
			if ('' == $this->fieldList[$field->alias])
			{
				$this->fieldList[$field->alias] = JHtmlProjects::select(
					'com_tracker.fields.' . $field->id,
					$field->id, $this->item->fields->get($field->id), 'N/A', ''
				);
			}
		}

		return parent::render();
	}
}
