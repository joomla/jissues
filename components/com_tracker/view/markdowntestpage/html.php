<?php
/**
 * @package     JTracker
 * @subpackage  View
 *
 * @copyright   Copyright (C) 2012 X. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * A Markdowntestpage view
 *
 * @package     JTracker
 * @subpackage  View
 * @since       1.0
 */
class TrackerViewMarkdowntestpageHtml extends JViewHtml
{
	/**
	 * @var JEventDispatcher
	 */
	protected $dispatcher;

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
		JPluginHelper::importPlugin('content');

		$this->dispatcher = JEventDispatcher::getInstance();

		return parent::render();
	}

	/**
	 * Output raw markdown and echo it out.
	 *
	 * @param   string   $raw      The raw string.
	 * @param   boolean  $comment  If TRUE, the raw text will be prepended for documentation.
	 *
	 * @return string
	 */
	protected function parse($raw, $comment = false)
	{
		$o       = new stdClass;
		$o->text = $raw;

		$html = array();

		$html[] = '<div class="row-fluid">';

		if ($comment)
		{
			$html[] = '<div class="span6">';
			$html[] = '<h3>Code</h3>';
			$html[] = '</div>';
			$html[] = '<div class="span6">';
			$html[] = '<h3>Output</h3>';
			$html[] = '</div>';
			$html[] = '</div>';

			$html[] = '<div class="row-fluid">';
			$html[] = '<div class="span6">';
			$html[] = '<pre>' . $o->text . '</pre>';
			$html[] = '</div>';
			$html[] = '<div class="span6">';
		}
		else
		{
			$html[] = '<div class="span12">';
		}

		$this->dispatcher->trigger('onContentPrepare', array('com_tracker.markdown', &$o, new JRegistry));

		$html[] = $o->text;
		$html[] = '</div>';

		$html[] = '</div>';

		return implode("\n", $html);
	}
}
