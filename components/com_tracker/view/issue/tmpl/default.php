<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$editor = JEditor::getInstance('kisskontent');
$editorParams = array('preview-url' => 'index.php?option=com_tracker&task=preview&format=raw')
?>

<h3><?php echo '[#' . $this->item->id . '] - ' . $this->item->title; ?></h3>
<div class="container-fluid">
	<div class="row-fluid">
		<div class="span5">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_INFO'); ?></h4>
			<table class="table">
				<tr>
					<td><strong><?php echo JText::_('JSTATUS'); ?></strong></td>
					<td><?php echo JText::_('COM_TRACKER_STATUS_' . strtoupper($this->item->status_title)); ?></td>
				</tr>
				<?php if ($this->item->gh_id) : ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?></strong></td>
					<td><a href="https://github.com/joomla/joomla-cms/issues/<?php echo $this->item->gh_id; ?>" target="_blank"><?php echo $this->item->gh_id; ?></a></td>
				</tr>
				<?php endif; ?>
				<?php if ($this->item->jc_id) : ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_HEADING_JOOMLACODE_ID'); ?></strong></td>
					<td>
						<a href="http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=<?php echo (int) $this->item->jc_id; ?>" target="_blank">
							<?php echo (int) $this->item->jc_id; ?>
						</a>
					</td>
				</tr>
				<?php endif; ?>
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
						<td><?php echo JHtml::_('date', $this->item->closed_date, 'DATE_FORMAT_LC2'); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ($this->item->modified != '0000-00-00 00:00:00') : ?>
					<tr>
						<td><strong><?php echo JText::_('COM_TRACKER_HEADING_DATE_MODIFIED'); ?></strong></td>
						<td><?php echo JHtml::_('date', $this->item->modified, 'DATE_FORMAT_LC2'); ?></td>
					</tr>
				<?php endif; ?>
				<?php if ($this->item->database_type): ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_DATABASE_TYPE'); ?></strong></td>
					<td><?php echo $this->item->database_type; ?></td>
				</tr>
				<?php endif; ?>
				<?php if($this->item->webserver): ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_WEBSERVER'); ?></strong></td>
					<td><?php echo $this->item->webserver; ?></td>
				</tr>
				<?php endif; ?>
				<?php if($this->item->php_version): ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_PHP_VERISON'); ?></strong></td>
					<td><?php echo $this->item->php_version; ?></td>
				</tr>
				<?php endif; ?>
				<?php if($this->item->browser): ?>
				<tr>
					<td><strong><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_BROWSER'); ?></strong></td>
					<td><?php echo $this->item->browser; ?></td>
				</tr>
				<?php endif; ?>
			</table>
			<a href="index.php?option=com_tracker&view=issues"><?php echo JTEXT::_('COM_TRACKER_BACK_TO_ISSUES'); ?></a>
		</div>
		<div class="span7">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_DESC'); ?></h4>
			<div class="well well-small issue">
				<p><?php echo $this->item->description; ?></p>
				<?php echo $editor->display('description', $this->item->description_raw, '100%', 100, 10, 10, false, 'editor-description', null, null, $editorParams); ?>
			</div>
		</div>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_COMMENTS'); ?></h4>
		</div>
	</div>

	<?php foreach ($this->comments as $i => $comment) : ?>
	<div class="row-fluid">
		<div class="span12">
			<div class="well well-small">
				<h5>
					<a href="#issue-comment-<?php echo $i + 1; ?>" id="issue-comment-<?php echo $i + 1; ?>">#<?php echo $i + 1; ?></a>
					<?php echo JText::sprintf('COM_TRACKER_LABEL_SUBMITTED_BY', $comment->submitter, $comment->created); ?>
				</h5>
				<p><?php echo $comment->text; ?></p>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

    <div class="row-fluid">
        <div class="span12">
            <hr />
            <h4>Add a comment...</h4>
			<?php echo $editor->display('comment', '', '100%', 100, 10, 10, false, 'editor-comment', null, null, $editorParams); ?>
        </div>
    </div>

</div>
