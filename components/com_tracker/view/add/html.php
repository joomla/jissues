<?php
/**
 * User: elkuku
 * Date: 15.10.12
 * Time: 22:01
 */

class TrackerViewAddHtml extends JViewHtml
{
	public function render()
	{
		$this->editor = JEditor::getInstance('kisskontent');

		$this->editorParams = array(
			'preview-url'     => 'index.php?option=com_tracker&task=preview&format=raw',
			'syntaxpage-link' => 'index.php?option=com_tracker&view=markdowntestpage',
		);

		return parent::render();
	}
}
