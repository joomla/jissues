<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\Factory;
use Joomla\Language\Text;
use Joomla\Registry\Registry;

/* SNIP ---> */

/*
 *This is a "module" that displays a "login with GitHub" / "Logout" link
 */

use Joomla\Tracker\Components\Tracker\HTML\HtmlGitHub;
use Joomla\Tracker\HTML\Html;

$user = Factory::$application->getUser();

if($user->id) :
	echo HtmlGithub::avatar($user, 20);
	echo Html::link('logout', sprintf('Logout %s', $user->username));
else :
	echo HtmlGithub::loginButton(Factory::$application->get('github.client_id'));
endif;

/* <--- SNAP */

// Initialize values to check for cells
$blockers = array('1', '2');

// Initialize Bootstrap Tooltips
$ttParams = array();
$ttParams['animation'] = true;
$ttParams['trigger']   = 'hover';
//JHtml::_('bootstrap.tooltip', '.hasTooltip', $ttParams);
//JHtml::_('formbehavior.chosen', 'select');

$filterStatus = $this->state->get('filter.status');
$fields = new Registry(Factory::$application->input->get('fields', array(), 'array'));

?>
<form action="<?php echo htmlspecialchars('index.php'); ?>" method="post" name="adminForm" id="adminForm" class="form-inline form-search">
	<?php //if (!$this->project) : ?>
	<!-- <div class="btn-group pull-left">
		<h2>Please select a project</h2>
		<?php //echo JHtmlProjects::select(0, '', 'onchange="document.adminForm.submit();"') ?>
	</div> -->
	<?php //else : ?>

	<div class="filters btn-toolbar clearfix">
		<div class="filter-search btn-group pull-left input-append">
			<label class="filter-search-lbl element-invisible" for="filter-search"><?php echo Text::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION'); ?></label>
			<input type="text" class="search-query input-xlarge" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" class="inputbox" onchange="document.adminForm.submit();" title="<?php echo Text::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION'); ?>" placeholder="<?php echo Text::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION'); ?>" />
			<button class="btn tip hasTooltip" type="submit" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><span class="icon-search"></span></button>
		</div>
		<div class="btn-group pull-left">
			<button class="btn tip hasTooltip" type="button" onclick="jQuery('#filter-search').val('');document.adminForm.submit();" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><span class="icon-remove"></span></button>
		</div>
		<div class="btn-group pull-left">
			<?php // @todo: project selector ?>
			<?php //echo JHtmlProjects::select((int) $this->state->get('filter.project'), Text::_('Filter by Project'), 'onchange="document.adminForm.submit();"') ?>
		</div>
		<div class="btn-group pull-right">
			<label for="status" class="element-invisible"><?php echo Text::_('COM_TRACKER_FILTER_STATUS'); ?></label>
			<select name="filter-status" id="filter-status" class="input-medium" onchange="document.adminForm.submit();">
				<option value=""><?php echo Text::_('COM_TRACKER_FILTER_STATUS');?></option>
				<?php //echo JHtml::_('select.options', JHtml::_('status.filter'), 'value', 'text', $filterStatus);?>
			</select>
		</div>
		<div class="btn-group pull-right hidden-phone">
			<label for="limit" class="element-invisible"><?php echo Text::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
			<?php //echo $this->pagination->getLimitBox(); ?>
		</div>
		<input type="hidden" name="filter_order" value="" />
		<input type="hidden" name="filter_order_Dir" value="" />
		<input type="hidden" name="limitstart" value="" />
	</div>
	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th width="2%" class="nowrap hidden-phone"><?php echo Text::_('JGRID_HEADING_ID'); ?></th>
				<th><?php echo Text::_('COM_TRACKER_HEADING_SUMMARY'); ?></th>
				<th width="5%"><?php echo Text::_('COM_TRACKER_HEADING_PRIORITY'); ?></th>
				<th width="10%"><?php echo Text::_('JSTATUS'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo Text::_('COM_TRACKER_HEADING_DATE_OPENED'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo Text::_('COM_TRACKER_HEADING_DATE_CLOSED'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo Text::_('COM_TRACKER_HEADING_LAST_MODIFIED'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (count($this->items) == 0) : ?>
			<tr>
				<td class="center" colspan="8">
					<?php echo Text::_('COM_TRACKER_NO_ITEMS_FOUND'); ?>
				</td>
			</tr>
		<?php else : ?>
		<?php foreach ($this->items as $i => $item) :
		$rowClass = '';
		if (in_array($item->priority, $blockers)) :
			$rowClass = 'class="error"';
		endif;
		if ($item->status == '4') :
			$rowClass = 'class="success"';
		endif
		?>
			<tr <?php echo $rowClass; ?>>
				<td class="center hidden-phone">
					<?php echo (int) $item->id; ?>
				</td>
				<td class="hasContext">
					<div class="hasTooltip" title="<?php //echo JHtml::_('string.truncate', $this->escape($item->description), 100); ?>">
						<a href="issue/<?php echo (int) $item->id;?>">
						<?php echo $this->escape($item->title); ?></a>
					</div>
					<?php if ($item->gh_id || $item->jc_id) : ?>
					<div class="small">
						<?php if ($item->gh_id) : ?>
						<?php echo Text::_('COM_TRACKER_HEADING_GITHUB_ID'); ?>
						<a href="https://github.com/<?= $this->project->gh_user . '/' . $this->project->gh_project ?>/issues/<?php echo (int) $item->gh_id; ?>" target="_blank">
							<?php echo (int) $item->gh_id; ?>
						</a>
						<?php endif; ?>
						<?php if ($item->gh_id && $item->jc_id) echo '<br />'; ?>
						<?php if ($item->jc_id) : ?>
						<?php echo Text::_('COM_TRACKER_HEADING_JOOMLACODE_ID'); ?>
						<a href="http://joomlacode.org/gf/project/joomla/tracker/?action=TrackerItemEdit&tracker_item_id=<?php echo (int) $item->jc_id; ?>" target="_blank">
							<?php echo (int) $item->jc_id; ?>
						</a>
						<?php endif; ?>
					</div>
					<?php endif; ?>
				</td>
				<td class="center">
					<?php
					if ($item->priority == 1) :
						$status_class = 'badge-important';
					elseif ($item->priority == 2) :
						$status_class = 'badge-warning';
					elseif ($item->priority == 3) :
						$status_class = 'badge-info';
					elseif ($item->priority == 4) :
						$status_class = 'badge-inverse';
					elseif ($item->priority == 5) :
						$status_class = '';
					endif;
					?>
					<span class="badge <?php echo $status_class; ?>">
						<?php echo (int) $item->priority; ?>
					</span>
				</td>
				<td>
					<?php echo Text::_('COM_TRACKER_STATUS_' . strtoupper($item->status_title)); ?>
				</td>
				<td class="nowrap small hidden-phone">
					<?php //echo JHtml::_('date', $item->opened, 'DATE_FORMAT_LC4'); ?>
				</td>
				<td class="nowrap small hidden-phone">
					<?php if ($item->closed_status) : ?>
						<?php //echo JHtml::_('date', $item->closed_date, 'DATE_FORMAT_LC4'); ?>
					<?php endif; ?>
				</td>
				<td class="nowrap small hidden-phone">
					<?php if ($item->modified != '0000-00-00 00:00:00') : ?>
						<?php //echo JHtml::_('date', $item->modified, 'DATE_FORMAT_LC4') . '<br />'; ?>
						<?php if ((bool) $item->modified_by) { ?>
						<?php //echo 'By ' . JFactory::getUser($item->modified_by)->username ?>
						<?php } ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<?php //echo $this->pagination->getListFooter(); ?>
	<?php //endif; ?>
	<input type="hidden" name="task" />
</form>
