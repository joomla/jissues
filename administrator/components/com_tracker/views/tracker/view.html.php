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
 * Default view class for the tracker component
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerViewTracker extends JViewLegacy
{
	protected $filterCategory = '';

	/**
	 * @var JInput
	 */
	protected $input = null;

	protected $project;

	protected $lists;

	/**
	 * @var JRegistry
	 */
	protected $fields = null;

	public function display($tpl = null)
	{
		$this->input = JFactory::getApplication()->input;

		$this->fields = new JRegistry($this->input->get('fields', array(), 'array'));

		$this->project = $this->fields->get('project');

		$this->lists = new JRegistry;

		if($this->project)
		{
			$this->lists->set('categories', JHtmlProjects::listing('com_tracker.' . $this->project.'.categories'));
			$this->lists->set('textfields', JHtmlProjects::listing('com_tracker.' . $this->project.'.textfields'));
			$this->lists->set('fields', JHtmlProjects::listing('com_tracker.' . $this->project.'.fields'));
			$this->lists->set('checkboxes', JHtmlProjects::listing('com_tracker.' . $this->project.'.checkboxes'));
		}

		parent::display($tpl);
	}
}
