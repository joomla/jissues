<?php
/**
 * @package    Kisskontent
 *
 * @copyright  Copyright (C) 2011-2012 Nikolai Plath - elkuku.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Markdown editor Plugin.
 *
 * @package  Kisskontent
 * @since    1.0
 */
class PlgEditorKisskontent extends JPlugin
{
	/**
	 * Method to handle the onInitEditor event.
	 *  - Initialises the Editor
	 *
	 * @since 1.5
	 *
	 * @return    string    JavaScript Initialization string
	 */
	public function onInit()
	{
		$txt = "<script type=\"text/javascript\">
					function insertAtCursor(myField, myValue) {
						if (document.selection) {
							// IE support
							myField.focus();
							sel = document.selection.createRange();
							sel.text = myValue;
						} else if (myField.selectionStart || myField.selectionStart == '0') {
							// MOZILLA/NETSCAPE support
							var startPos = myField.selectionStart;
							var endPos = myField.selectionEnd;
							myField.value = myField.value.substring(0, startPos)
								+ myValue
								+ myField.value.substring(endPos, myField.value.length);
						} else {
							myField.value += myValue;
						}
					}
				</script>";

		return $txt;
	}

	/**
	 * Copy editor content to form field.
	 *
	 * Not applicable in this editor.
	 *
	 * @return void
	 */
	public function onSave()
	{
		return;
	}

	/**
	 * Get the editor content.
	 *
	 * @param   string  $id  The id of the editor field.
	 *
	 * @return string
	 */
	public function onGetContent($id)
	{
		return "document.getElementById('$id').value;\n";
	}

	/**
	 * Set the editor content.
	 *
	 * @param   string  $id    The id of the editor field.
	 * @param   string  $html  The content to set.
	 *
	 * @return string
	 */
	public function onSetContent($id, $html)
	{
		return "document.getElementById('$id').value = $html;\n";
	}

	/**
	 * Get the insert method.
	 *
	 * @param   string  $id  Unused.
	 *
	 * @return string
	 */
	public function onGetInsertMethod($id)
	{
		static $done = false;

		// Do this only once.
		if (!$done)
		{
			JFactory::getDocument()->addScriptDeclaration(
				"\tfunction jInsertEditorText(text, editor) {
				insertAtCursor(document.getElementById(editor), text);
			}");

			$done = true;
		}

		return true;
	}

	/**
	 * Display the editor area.
	 *
	 * @param   string   $name     The control name.
	 * @param   string   $content  The content to set.
	 * @param   string   $width    The width of the text area (px or %).
	 * @param   string   $height   The height of the text area (px or %).
	 * @param   int      $col      The number of columns for the textarea.
	 * @param   int      $row      The number of rows for the textarea.
	 * @param   boolean  $buttons  True and the editor buttons will be displayed.
	 * @param   string   $id       An optional ID for the textarea (note: since 1.6). If not supplied the name is used.
	 * @param   string   $asset    ?
	 * @param   object   $author   ?
	 * @param   array    $params   Associative array of editor parameters.
	 *
	 * @internal param string $html The contents of the text area.
	 * @return    string
	 */
	public function onDisplay($name, $content, $width, $height, $col, $row, $buttons = true, $id = null, $asset = null, $author = null, $params = array())
	{
		if (empty($id))
		{
			$id = $name;
		}

		// Replace [] characters in the $name param, copy it for the textarea name
		$tname = $name;
		$name = str_replace('[', '-', $name);
		$name = str_replace(']', '', $name);

		// Only add "px" to width and height if they are not given as a percentage
		if (is_numeric($width))
		{
			$width .= 'px';
		}

		if (is_numeric($height))
		{
			$height .= 'px';
		}

		$buttons = $this->_displayButtons($id, $buttons, $asset, $author);

		$syntaxPageLink = $this->params->get('syntaxpage-link');

		$html = array();

		if ($syntaxPageLink)
		{
			$html[] = '<div class="pull-right">';
			$html[] = sprintf('Text is parsed with %s', JHtml::link($syntaxPageLink, 'Enhanced ElephantMarkdown'));
			$html[] = '</div>';
		}

		$html[] = '<ul class="nav nav-tabs" id="' . $name . '-tabs">';
		$html[] = '  <li class="active"><a href="#' . $name . '-write" data-toggle="tab"">Write</a></li>';
		$html[] = '  <li><a href="#' . $name . '-preview" data-toggle="tab">Preview</a></li>';
		$html[] = '</ul>';

		$html[] = '<div class="tab-content">';
		$html[] = '  <div class="tab-pane active" id="' . $name . '-write">';
		$html[] = "   <textarea name=\"$tname\" id=\"$id\" cols=\"$col\" rows=\"$row\" style=\"width: $width; height: $height;\">$content</textarea>";
		$html[] = $buttons;
		$html[] = '  </div>';
		$html[] = '  <div class="tab-pane fade well well-small" id="' . $name . '-preview" style="min-height: ' . $height . '">Loading...</div>';
		$html[] = '</div>';

		$url = $this->params->get('preview-url');

		if ($url)
		{
			$loadingHtml = 'Loading...';
			$postParams  = "{text: document.getElementById('{$id}').value}";

			JFactory::getDocument()->addScriptDeclaration(
				"
jQuery(document).ready(function($) {
    $('#{$name}-tabs a').click(function(e) {
    e.preventDefault();
    $('#{$name}-preview').html('{$loadingHtml}').load('{$url}', {$postParams});
    jQuery(this).tab('show');
    });
});"
			);
		}

		return implode("\n", $html);
	}

	/**
	 * Display editor buttons.
	 *
	 * @param   string  $name     The name.
	 * @param   mixed   $buttons  The buttons to display.
	 * @param   string  $asset    ?.
	 * @param   string  $author   ?.
	 *
	 * @return string
	 */
	public function _displayButtons($name, $buttons, $asset, $author)
	{
		// Load modal popup behavior
		// @todo mootools - JHtml::_('behavior.modal', 'a.modal-button');

		$args['name']  = $name;
		$args['event'] = 'onGetInsertMethod';

		$return    = '';
		$results[] = $this->update($args);

		foreach ($results as $result)
		{
			if (is_string($result) && trim($result))
			{
				$return .= $result;
			}
		}

		if (is_array($buttons) || (is_bool($buttons) && $buttons))
		{
			$results = $this->_subject->getButtons($name, $buttons, $asset, $author);

			// This will allow plugins to attach buttons or change the behavior on the fly using AJAX
			$return .= "\n<div id=\"editor-xtd-buttons\" class=\"btn-toolbar pull-left\">\n";
			$return .= "\n<div class=\"btn-toolbar\">\n";

			foreach ($results as $button)
			{
				// Results should be an object
				if ($button->get('name'))
				{
					$modal   = ($button->get('modal')) ? 'class="modal-button btn"' : null;
					$href    = ($button->get('link')) ? 'class="btn" href="' . JURI::base() . $button->get('link') . '"' : null;
					$onclick = ($button->get('onclick')) ? 'onclick="' . $button->get('onclick') . '"' : null;
					$title   = ($button->get('title')) ? $button->get('title') : $button->get('text');
					$return .= "<a " . $modal . " title=\"" . $title . "\" " . $href . " " . $onclick . " rel=\"" . $button->get('options') . "\"><i class=\"icon-" . $button->get('name') . "\"></i> " . $button->get('text') . "</a>\n";
				}
			}

			$return .= "</div>\n";
			$return .= "</div>\n";
			$return .= "<div class=\"clearfix\"></div>\n";
		}

		return $return;
	}
}
