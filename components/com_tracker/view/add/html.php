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
 * The issues add view
 *
 * @package     JTracker
 * @subpackage  com_tracker
 * @since       1.0
 */
class TrackerViewAddHtml extends JViewHtml
{
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
	 * @var ?
	 */
	protected $project;

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
		$this->editor = JEditor::getInstance('kisskontent');

		$this->editorParams = array(
			'preview-url'     => 'index.php?option=com_tracker&task=preview',
			'syntaxpage-link' => 'index.php?option=com_tracker&view=markdowntestpage',
		);

		// @todo Before adding a new issue we need the project id !
		$this->project = new stdClass;
		$this->project->id = 1;

		return parent::render();
	}
}
