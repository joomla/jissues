<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var TrackerViewEditHtml $this */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');

// Set up the options array for the priority field
$priorityOptions = array();
$priorityOptions['id'] = 'jform_priority';
$priorityOptions['size'] = '5';
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<h3><?php echo JText::_('Edit Item') . ' [#' . $this->item->id . ']'; ?></h3>
		<div class="row-fluid">
			<div class="span12">
				<div class="input-prepend">
				<span class="add-on"><strong><?php echo JText::_('COM_TRACKER_HEADING_SUMMARY'); ?></strong></span>
				<input type="text" name="jform[title]" id="jform_title" class="input-xxlarge" value="<?php echo htmlspecialchars($this->item->title, ENT_COMPAT, 'UTF-8'); ?>" maxlength="100">
			</div>
		</div>

		<div class="row-fluid">
			<div class="span5">
				<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_INFO'); ?></h4>
				<table class="table">
					<tr>
						<th><?php echo JText::_('JSTATUS'); ?></th>
						<td><?php echo JHtmlStatus::options(); ?></td>
					</tr>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?></th>
						<td><input type="text" name="jform[gh_id]" id="jform_gh_id" class="input-small" value="<?php echo htmlspecialchars($this->item->gh_id, ENT_COMPAT, 'UTF-8'); ?>" maxlength="5"></td>
					</tr>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_JOOMLACODE_ID'); ?></th>
						<td><input type="text" name="jform[jc_id]" id="jform_jc_id" class="input-small" value="<?php echo htmlspecialchars($this->item->jc_id, ENT_COMPAT, 'UTF-8'); ?>" maxlength="5"></td>
					</tr>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_PRIORITY'); ?></th>
						<td><?php echo JHtmlSelect::integerlist(1, 5, 1, 'jform[priority]', $priorityOptions, $this->item->priority); ?></td>
					</tr>
					<?php if ($this->item->patch_url) : ?>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_PATCH_URL'); ?></th>
						<td><input type="text" name="jform[patch_url]" id="jform_patch_url" value="<?php echo htmlspecialchars($this->item->patch_url, ENT_COMPAT, 'UTF-8'); ?>"></td>
					</tr>
					<?php endif; ?>
					<tr>
						<td>
							<strong><?php echo JText::_('COM_TRACKER_HEADING_DATE_OPENED'); ?></strong>
						</td>
						<td><?php echo JHtml::_('date', $this->item->opened, 'DATE_FORMAT_LC2'); ?></td>
					</tr>
					<?php if ($this->item->closed) : ?>
						<tr>
							<th><?php echo JText::_('COM_TRACKER_HEADING_DATE_CLOSED'); ?></th>
							<td><?php echo JHtml::_('date', $this->item->closed_date, 'DATE_FORMAT_LC2'); ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($this->item->modified != '0000-00-00 00:00:00') : ?>
						<tr>
							<th><?php echo JText::_('COM_TRACKER_HEADING_DATE_MODIFIED'); ?></th>
							<td><?php echo JHtml::_('date', $this->item->modified, 'DATE_FORMAT_LC2'); ?></td>
						</tr>
					<?php endif; ?>
					<?php include $this->getPath('fields'); ?>
				</table>

			</div>
			<div class="span7">
				<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_DESC'); ?></h4>
				<div class="well well-small issue">
					<?php echo $this->editor->display('description', $this->item->description, '100%', 300, 10, 10, false, 'editor-comment', null, null, $this->editorParams); ?>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" />
</form>
