<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Initialize values to check for cells
$blockers = array('1', '2');

// Initialize Bootstrap Tooltips
$ttParams = array();
$ttParams['animation'] = true;
$ttParams['trigger']   = 'hover';
JHtml::_('bootstrap.tooltip', '.hasTooltip', $ttParams);
JHtml::_('formbehavior.chosen', 'select');

$filterStatus = $this->state->get('filter.status')
?>
<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form-inline form-search">
	<div class="filters btn-toolbar clearfix">
		<div class="filter-search btn-group pull-left input-append">
			<label class="filter-search-lbl element-invisible" for="filter-search"><?php echo JText::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION'); ?></label>
			<input type="text" class="search-query input-xlarge" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" class="inputbox" onchange="document.adminForm.submit();" title="<?php echo JText::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION'); ?>" placeholder="<?php echo JText::_('COM_TRACKER_FILTER_SEARCH_DESCRIPTION'); ?>" />
			<button class="btn tip hasTooltip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
		</div>
		<div class="btn-group pull-left">
			<button class="btn tip hasTooltip" type="button" onclick="jQuery('#filter-search').val('');document.adminForm.submit();" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
		</div>
		<div class="btn-group pull-right">
			<label for="status" class="element-invisible"><?php echo JText::_('COM_TRACKER_FILTER_STATUS'); ?></label>
			<select name="status" id="filter-status" class="input-medium" onchange="document.adminForm.submit();">
				<option value=""><?php echo JText::_('COM_TRACKER_FILTER_STATUS');?></option>
				<?php echo JHtml::_('select.options', JHtml::_('status.options'), 'value', 'text', $filterStatus);?>
			</select>
		</div>
		<input type="hidden" name="filter_order" value="" />
		<input type="hidden" name="filter_order_Dir" value="" />
		<input type="hidden" name="limitstart" value="" />
	</div>
	<table class="table table-bordered table-striped">
		<thead>
			<tr>
				<th width="2%" class="nowrap hidden-phone"><?php echo JText::_('JGRID_HEADING_ID'); ?></th>
				<th class="nowrap hidden-phone"><?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?></th>
				<th><?php echo JText::_('COM_TRACKER_HEADING_SUMMARY'); ?></th>
				<th width="5%"><?php echo JText::_('COM_TRACKER_HEADING_PRIORITY'); ?></th>
				<th width="10%"><?php echo JText::_('JSTATUS'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo JText::_('JCATEGORY'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo JText::_('COM_TRACKER_HEADING_DATE_OPENED'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo JText::_('COM_TRACKER_HEADING_DATE_CLOSED'); ?></th>
				<th width="10%" class="hidden-phone"><?php echo JText::_('COM_TRACKER_HEADING_LAST_MODIFIED'); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if (count($this->items) == 0) : ?>
			<tr>
				<td class="center" colspan="8">
					<?php echo JText::_('COM_TRACKER_NO_ITEMS_FOUND'); ?>
				</td>
			</tr>
		<?php else : ?>
		<?php foreach ($this->items as $i => $item) :
		$rowClass = '';
		if (in_array($item->priority, $blockers)) {
			$rowClass = 'class="error"';
		}
		if ($item->status == '4') {
			$rowClass = 'class="success"';
		}
		?>
			<tr <?php echo $rowClass; ?>>
				<td class="center hidden-phone">
					<?php echo (int) $item->id; ?>
				</td>
				<td class="center hidden-phone">
					<?php if ($item->gh_id) : ?>
					<a href="https://github.com/joomla/joomla-cms/issues/<?php echo (int) $item->gh_id; ?>" target="_blank">
						<?php echo (int) $item->gh_id; ?>
					</a>
					<?php else : ?>
					<?php echo JText::_('COM_TRACKER_NOT_APPLICABLE_SHORT'); ?>
					<?php endif; ?>
				</td>
				<td class="hasContext">
					<div class="hasTooltip" title="<?php echo JHtml::_('string.truncate', $item->description, 100); ?>">
						<a href="index.php?option=com_tracker&view=issue&id=<?php echo (int) $item->id;?>">
						<?php echo $this->escape($item->title); ?></a>
					</div>
				</td>
				<td>
					<?php echo (int) $item->priority; ?>
				</td>
				<td>
					<?php echo JText::_('COM_TRACKER_STATUS_' . strtoupper($item->status_title)); ?>
				</td>
				<td class="hidden-phone">
					N/A
				</td>
				<td class="nowrap small hidden-phone">
					<?php echo JHtml::_('date', $item->opened, 'DATE_FORMAT_LC4'); ?>
				</td>
				<td class="nowrap small hidden-phone">
					<?php if ($item->closed_status) : ?>
						<?php echo JHtml::_('date', $item->closed, 'DATE_FORMAT_LC4'); ?>
					<?php endif; ?>
				</td>
				<td class="nowrap small hidden-phone">
					<?php if ($item->modified != '0000-00-00 00:00:00') : ?>
						<?php echo JHtml::_('date', $item->modified, 'DATE_FORMAT_LC4'); ?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</form>
