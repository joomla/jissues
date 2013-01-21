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

$gh_user_name = JFactory::getSession()->get('gh_user_name');

// @todo support for avatars from gravatar or github
$gh_user_avatar = 'media/jtracker/avatars/amor.png';

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

		<ul class="nav nav-tabs">
			<li class="active"><a href="#comments" data-toggle="tab"><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_COMMENTS'); ?></a></li>
			<li><a href="#events" data-toggle="tab"><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_EVENTS'); ?></a></li>
		</ul>
		<div class="tab-content">
		    <div class="tab-pane active" id="comments">
				<?php if (count($this->activity['comments']) >= 1) : ?>
				<?php foreach ($this->activity['comments'] as $i => $comment) : ?>
				<div class="row-fluid">
					<div class="span12">
						<div class="well well-small">
							<h5>
								<a href="#issue-comment-<?php echo $i + 1; ?>" id="issue-comment-<?php echo $i + 1; ?>">#<?php echo $i + 1; ?></a>
								<?php echo JText::sprintf('COM_TRACKER_LABEL_SUBMITTED_BY', $comment->user, $comment->created); ?>
							</h5>
							<p><?php echo $comment->text; ?></p>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
				<?php else : ?>
				<div class="row-fluid">
					<div class="span12">
						<div class="alert alert-info">
							<h5>No comments on this issue.</h5>
						</div>
					</div>
				</div>
				<?php endif; ?>
			    <?php if ($gh_user_name) : ?>
				    <div class="well well-small">
					    <a name="comment-section"></a>
					    <form method="post" action="index.php">
						    <div class="row-fluid">
						        <div class="span1 pagination-centered">
							        <img src="<?php echo $gh_user_avatar ?>" class="img-polaroid" alt="Avatar <?php echo $gh_user_name ?>" title="Avatar <?php echo $gh_user_name ?>"/>
						        </div>
							    <div class="span11">
									<textarea name="comment" placeholder="Add a comment..." style="width: 99%;"></textarea>
							    </div>
							 </div>
						    <div class="row-fluid">
							    <div class="span12">
								    <input type="submit" class="btn btn-success btn-large pull-right" value="Comment" />
							    </div>
							</div>
							<input type="hidden" name="option" value="com_tracker" />
							<input type="hidden" name="task" value="comment" />
						    <input type="hidden" name="usr_return" value="<?php echo base64_encode(JUri::getInstance().'#comment-section') ?>">
						</form>
				    </div>
			    <?php else : ?>
				    <?php echo JHtmlGithub::loginButton('Login with GitHub to add a comment', JUri::current().'#comment-section') ?>
			    <?php endif; ?>
			</div>
			<div class="tab-pane" id="events">
				<?php foreach ($this->activity['events'] as $i => $event) : ?>
				<?php $langKey = 'COM_TRACKER_EVENT_' . strtoupper($event->event); ?>
				<div class="row-fluid">
					<div class="span12">
						<div class="well well-small">
							<h5>
								<?php echo JText::sprintf($langKey, $event->user, $event->created); ?>
							</h5>
							<?php
							switch($event->event):
								case 'change' :
									echo JHtmlTrackerevent::displayTable($this->project, $event->text);
									break;
								default :
									break;

							endswitch;
							?>
						</div>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<input type="hidden" name="task"/>
	<?php echo JHtml::_('form.token'); ?>
</form>
