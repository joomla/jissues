<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>
<h3><?php echo $this->item->title; ?></h3>
<div class="container-fluid">
	<div class="row-fluid">
		<div class="span5">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_INFO'); ?></h4>
			<table class="table">
				<tr>
					<td><strong><?php echo JText::_('JSTATUS'); ?></strong></td>
					<td><?php echo JText::_('COM_TRACKER_STATUS_' . strtoupper($this->item->status)); ?></td>
				</tr>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?></strong></td>
					<td><a href="https://github.com/joomla/joomla-cms/issues/<?php echo $this->item->gh_id; ?>" target="_blank"><?php echo $this->item->gh_id; ?></a></td>
				</tr>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_HEADING_PRIORITY'); ?></strong></td>
					<td>
						<?php if($this->item->priority == 1)
						{
							$status_class = 'badge-important';
						}
						elseif ($this->item->priority == 2)
						{
							$status_class = 'badge-warning';
						}
						elseif ($this->item->priority == 3)
						{
							$status_class = 'badge-info';
						}
						elseif ($this->item->priority == 4)
						{
							$status_class = 'badge-inverse';
						}
						elseif ($this->item->priority == 5)
						{
							$status_class = '';
						}
						?>
						<span class="badge <?php echo $status_class; ?>">
							<?php echo $this->item->priority; ?>
						</span>
					</td>
				</tr>
				<?php if ($this->item->patch_url) : ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_PATCH_URL'); ?></strong></td>
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
						<td><strong><?php echo JText::_('COM_TRACKER_HEADING_DATE_CLOSED'); ?></strong></td>
						<td><?php echo JHtml::_('date', $this->item->closed, 'DATE_FORMAT_LC2'); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ($this->item->modified != '0000-00-00 00:00:00') : ?>
					<tr>
						<td><strong><?php echo JText::_('COM_TRACKER_HEADING_DATE_MODIFIED'); ?></strong></td>
						<td><?php echo JHtml::_('date', $this->item->modified, 'DATE_FORMAT_LC2'); ?></td>
					</tr>
				<?php endif; ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_DATABASE_TYPE'); ?></strong></td>
					<td><?php echo $this->item->database_type; ?></td>
				</tr>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_WEBSERVER'); ?></strong></td>
					<td><?php echo $this->item->webserver; ?></td>
				</tr>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_PHP_VERISON'); ?></strong></td>
					<td><?php echo $this->item->php_version; ?></td>
				</tr>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_BROWSER'); ?></strong></td>
					<td><?php echo $this->item->browser; ?></td>
				</tr>
			</table>
			<a href="index.php?option=com_tracker&view=issues"><?php echo JTEXT::_('COM_TRACKER_BACK_TO_ISSUES'); ?></a>
		</div>
		<div class="span7">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_DESC'); ?></h4>
			<div class="well well-small issue">
				<p><?php echo $this->item->description; ?></p>
			</div>
		</div>
	</div>
	<?php if ($this->comments) : ?>
	<div class="row-fluid">
		<div class="span12">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_COMMENTS'); ?></h4>
		</div>
	</div>
	<?php foreach ($this->comments as $comment) : ?>
	<div class="row-fluid">
		<div class="span12">
			<div class="well well-small">
				<h5><?php echo JText::sprintf('COM_TRACKER_LABEL_SUBMITTED_BY', $comment->submitter, $comment->created); ?></h5>
				<p><?php echo $comment->text; ?></p>
			</div>
		</div>
	</div>
	<?php endforeach; ?>
	<?php endif; ?>
</div>
