<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var TrackerViewIssueHtml $this */

defined('_JEXEC') or die;

?>
<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm"
      id="adminForm">
	<div class="container-fluid">
		<h3><?php echo sprintf('%1$s: [#%2$d] %3$s', $this->project->title, $this->item->id, $this->item->title); ?></h3>

		<div class="row-fluid">
			<div class="span5">
				<?php if ($this->item->rel_id) : ?>
					<div class="alert">
						<?php echo JText::_('COM_TRACKER_RELTYPE_' . $this->item->rel_name) ?>
						<?php echo JHtmlIssues::link($this->item->rel_id, $this->item->rel_closed) ?>
					</div>
				<?php endif; ?>

				<?php if ($this->item->relations_f) : ?>
					<div class="well well-small">
						<h5><?php echo JText::_('COM_TRACKER_LABEL_REFERENCES'); ?></h5>
						<?php foreach ($this->item->relations_f as $rel_name => $relations) : ?>
						<strong><?php echo JText::_('COM_TRACKER_RELTYPE_AS_' . $rel_name) ?></strong>
							<?php foreach ($relations as $relation) : ?>
								<?php echo JHtmlIssues::link($relation->id, $relation->closed) ?>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_INFO'); ?></h4>
				<table class="table">
					<tr>
						<th><?php echo JText::_('JSTATUS'); ?></th>
						<td><?php echo JText::_('COM_TRACKER_STATUS_' . strtoupper($this->item->status_title)); ?></td>
					</tr>
					<?php if ($this->item->closed_sha) : ?>
						<tr>
							<th><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_CLOSED_SHA'); ?></th>
							<td><?php echo JHtmlIssues::commit($this->project, $this->item->closed_sha) ?></td>
						</tr>
					<?php endif; ?>
					<?php if ($this->item->gh_id) : ?>
						<tr>
							<th><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?></th>
							<td><a href="https://github.com/<?php echo $this->project->gh_user . '/' . $this->project->gh_project . '/issues/' . $this->item->gh_id; ?>"
							       target="_blank"><?php echo $this->item->gh_id; ?></a></td>
						</tr>
					<?php endif; ?>
					<?php if ($this->item->jc_id) : ?>
						<tr>
							<th><?php echo JText::_('COM_TRACKER_HEADING_JOOMLACODE_ID'); ?></th>
							<td>
								<a href="http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=<?php echo (int) $this->item->jc_id; ?>"
								   target="_blank">
									<?php echo (int) $this->item->jc_id; ?>
								</a>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<th><?php echo JText::_('COM_TRACKER_HEADING_PRIORITY'); ?></th>
						<td>
							<?php
							if ($this->item->priority == 1) :
								$status_class = 'badge-important';
							elseif ($this->item->priority == 2) :
								$status_class = 'badge-warning';
							elseif ($this->item->priority == 3) :
								$status_class = 'badge-info';
							elseif ($this->item->priority == 4) :
								$status_class = 'badge-inverse';
							elseif ($this->item->priority == 5) :
								$status_class = '';
							endif;
							?>
							<span class="badge <?php echo $status_class; ?>">
								<?php echo $this->item->priority; ?>
							</span>
						</td>
					</tr>
					<?php if ($this->item->patch_url) : ?>
						<tr>
							<th><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_PATCH_URL'); ?></th>
							<td><a href="<?php echo $this->item->patch_url; ?>"
							       target="_blank"><?php echo $this->item->patch_url; ?></a></td>
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

					<!-- Select lists ! -->
					<?php foreach (JHtmlCustomfields::items('fields', $this->item->project_id) as $field) : ?>
						<tr>
							<th><?= JText::_($field->title); ?></th>
							<td><?= isset($this->fieldsData[$field->id]) && $this->fieldsData[$field->id]->field_value ? $this->fieldsData[$field->id]->field_value : 'N/A'; ?>
						</tr>
					<?php endforeach; ?>

					<!-- Text fields -->
					<?php foreach (JHtmlCustomfields::items('textfields', $this->item->project_id) as $field) : ?>
						<tr>
							<th><?= JText::_($field->title); ?></th>
							<td><?= isset($this->fieldsData[$field->id]) && $this->fieldsData[$field->id]->value ? $this->fieldsData[$field->id]->value : 'N/A'; ?>
						</tr>
					<?php endforeach; ?>

					<!-- Checkboxes -->
					<?php foreach (JHtmlCustomfields::items('checkboxes', $this->item->project_id) as $field) : ?>
						<tr>
							<th><?= JText::_($field->title); ?></th>
							<td><?= isset($this->fieldsData[$field->id]) ? 'yes' : 'no'; ?>
						</tr>
					<?php endforeach; ?>

				</table>

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
		<?php endif; ?>
	</div>
	<input type="hidden" name="task"/>
	<?php echo JHtml::_('form.token'); ?>
</form>
