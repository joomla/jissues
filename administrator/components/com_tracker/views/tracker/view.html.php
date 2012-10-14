<?php
/**
 * User: elkuku
 * Date: 10.10.12
 * Time: 18:35
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
			$this->lists->set('fields', JHtmlProjects::listing('com_tracker.' . $this->project.'.fields'));
		}

		parent::display($tpl);
	}
}
