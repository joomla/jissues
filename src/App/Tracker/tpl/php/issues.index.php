<?php
/**
 * User: elkuku
 * Date: 16.05.13
 * Time: 14:46
 */
use Joomla\Language\Text;

/*
 * {{ uri.base.path }}
 * <?php echo $this->uri->base->path ?>
var_dump($this->items);
var_dump($this->userxXXX);
var_dump($this);

{# Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved. #}
{# GNU General Public License version 2 or later; see LICENSE.txt #}
<?php extends "index.twig" ?>

<?php block title ?>Issues List<?php endblock ?>

<?php block headerText ?>{{ project.title }}<?php endblock ?>

<?php block content ?>
*/
?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="form-inline form-search">
	<div class="filters btn-toolbar clearfix">
		<div class="filter-search btn-group pull-left input-append">
			<label class="filter-search-lbl element-invisible"
			       for="filter-search"><?php echo Text::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION') ?></label>
			<input type="text" class="search-query input-xlarge" name="filter-search" id="filter-search"
			       value="<?php echo $this->state->get('list.filter') ?>" onchange="document.adminForm.submit();"
			       title="<?php echo Text::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION') ?>"
			       placeholder="<?php echo Text::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION') ?>"/>
			<button class="btn tip hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT') ?>">
				<span class="icon-search"></span></button>
		</div>
		<div class="btn-group pull-left">
			<button class="btn tip hasTooltip" type="button"
			        onclick="jQuery('#filter-search').val('');document.adminForm.submit();"
			        title="<?php echo Text::_('JSEARCH_FILTER_CLEAR') ?>"><span class="icon-remove"></span></button>
		</div>
	</div>
	<table class="table table-bordered table-striped">
		<thead>
		<tr>
			<th width="2%" class="nowrap hidden-phone"><?php echo Text::_('JGRID_HEADING_ID') ?></th>
			<th><?php echo Text::_('COM_TRACKER_HEADING_SUMMARY') ?></th>
			<th width="5%"><?php echo Text::_('COM_TRACKER_HEADING_PRIORITY') ?></th>
			<th width="10%"><?php echo Text::_('JSTATUS') ?></th>
			<th width="10%" class="hidden-phone"><?php echo Text::_('COM_TRACKER_HEADING_DATE_OPENED') ?></th>
			<th width="10%" class="hidden-phone"><?php echo Text::_('COM_TRACKER_HEADING_DATE_CLOSED') ?></th>
			<th width="10%" class="hidden-phone"><?php echo Text::_('COM_TRACKER_HEADING_LAST_MODIFIED') ?></th>
		</tr>
		</thead>
		<tbody>
		<?php if ($this->items) : ?>
			<?php $blockers = array(1, 2) ?>
			<?php foreach ($this->items as $item) : ?>

				<?php $rowClass = '' ?>
				<?php if (in_array($item->priority, $blockers)) : ?>
					<?php $rowClass = ' class="error"' ?>
				<?php endif ?>
				<?php if ($item->status == 4) : ?>
					<?php $rowClass = ' class="success"' ?>
				<?php endif ?>

				<tr<?php echo $rowClass ?>>
					<td class="center hidden-phone">
						<?php echo $item->id ?>
					</td>
					<td class="hasContext">
						<div class="hasTooltip" title="">
							<a href="<?php echo $this->uri->base->path ?>tracker/<?php echo $this->project->alias ?>/<?php echo $item->issue_number ?>">
								<?php echo $item->title ?>
							</a>
						</div>
						<?php if ($item->issue_number or $item->foreign_number) : ?>
							<div class="small">
								<?php if ($item->issue_number) : ?>
									<?php echo Text::_('COM_TRACKER_HEADING_GITHUB_ID') ?>
									<a href="https://github.com/<?php echo $this->project->gh_user ?>/<?php echo $this->project->gh_project ?>/issues/<?php echo $item->issue_number ?>"
									   target="_blank">
										<?php echo $item->issue_number ?>
									</a>
								<?php endif ?>
								<?php if ($item->issue_number and $item->foreign_number) : ?>
									<br/>
								<?php endif ?>
								<?php if ($item->foreign_number) : ?>
									<?php echo Text::_('COM_TRACKER_HEADING_JOOMLACODE_ID') ?>
									<a href="http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=<?php echo $item->foreign_number ?>"
									   target="_blank">
										<?php echo $item->foreign_number ?>
									</a>
								<?php endif ?>
							</div>
						<?php endif ?>
					</td>
					<td class="center">
						<?php if ($item->priority == 1) : ?>
							<?php $statusClass = 'badge-important' ?>
						<?php elseif ($item->priority == 2) : ?>
							<?php $statusClass = 'badge-warning' ?>
						<?php
						elseif ($item->priority == 3) : ?>
							<?php $statusClass = 'badge-info' ?>
						<?php
						elseif ($item->priority == 4) : ?>
							<?php $statusClass = 'badge-inverse' ?>
						<?php
						elseif ($item->priority == 4) : ?>
							<?php $statusClass = '' ?>
						<?php endif ?>
						<span class="badge <?php echo $statusClass ?>"><?php echo $item->priority ?></span>
					</td>

					<td>
						<?php echo Text::_('COM_TRACKER_STATUS_' . $item->status_title) ?>
					</td>

					<td class="nowrap small hidden-phone">
						<?php echo $item->opened_date //|date('Y-m-d') ?>
					</td>
					<td class="nowrap small hidden-phone">
						<?php if ($item->closed_status) : ?>
							<?php echo $item->closed_date //|date('Y-m-d') ?>
						<?php endif ?>
					</td>
					<td class="nowrap small hidden-phone">
						<?php if ($item->modified_date != '0000-00-00 00:00:00') : ?>
							<?php echo $item->modified_date //|date('Y-m-d') ?>
							<?php if ($item->modified_by) : ?>
								<br/>
								{#<?php //echo 'By ' . JFactory::getUser($item->modified_by)->username ?>#}
							<?php endif ?>
						<?php endif ?>
					</td>
				</tr>
			<?php endforeach ?>
		<?php else : ?>
			<tr>
				<td class="center" colspan="8">
					<?php echo Text::_('COM_TRACKER_NO_ITEMS_FOUND') ?>
				</td>
			</tr>
		<?php endif ?>
		</tbody>
	</table>
</form>
