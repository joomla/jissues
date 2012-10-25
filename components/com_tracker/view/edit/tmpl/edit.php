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
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
	<div class="container-fluid">
		<h3><?php echo JText::_('Edit Item') . ' [#' . $this->item->id . ']'; ?></h3>

		<div class="row-fluid">
			<div class="span5">
				<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_INFO'); ?></h4>
				<table class="table">
					<tr>
						<th><?php echo JText::_('JSTATUS'); ?></th>
						<td><?php echo JHtmlStatus::options(); ?></td>
					</tr>
					<?php if ($this->item->gh_id) : ?>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?></th>
						<td><a href="https://github.com/joomla/joomla-cms/issues/<?php echo $this->item->gh_id; ?>" target="_blank"><?php echo $this->item->gh_id; ?></a></td>
					</tr>
					<?php endif; ?>
					<?php if ($this->item->jc_id) : ?>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_JOOMLACODE_ID'); ?></th>
						<td>
							<a href="http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=<?php echo (int) $this->item->jc_id; ?>" target="_blank">
								<?php echo (int) $this->item->jc_id; ?>
							</a>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_PRIORITY'); ?></th>
						<td><?php echo JHtmlSelect::integerlist(1, 5, 1, 'priority-select', 'size="5"', $this->item->priority); ?></td>
					</tr>
					<?php if ($this->item->patch_url) : ?>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_PATCH_URL'); ?></th>
						<td><a href="<?php echo $this->item->patch_url; ?>" target="_blank"><?php echo $this->item->patch_url; ?></a></td>
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
					<p><?php echo $this->item->description; ?></p>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" />
</form>
