<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Initialize values to check for cells
$blockers = array(1, 2);

// Initialize Bootstrap Tooltips
$ttParams = array();
$ttParams['animation'] = true;
$ttParams['trigger']   = 'hover';
JHtml::_('bootstrap.tooltip', '.hasTooltip', $ttParams);
?>
<table class="table table-bordered table-striped">
	<thead>
		<tr>
			<th width="2%" class="nowrap hidden-phone">ID</th>
			<th>Summary</th>
			<th width="5%">Priority</th>
			<th width="5%">Status</th>
			<th width="10%" class="hidden-phone">Category</th>
			<th width="10%" class="hidden-phone">Date Opened</th>
			<th width="10%" class="hidden-phone">Date Closed</th>
			<th width="10%" class="hidden-phone">Last Modified</th>
		</tr>
	</thead>
	<tbody>
	<?php if (count($this->items) == 0) : ?>
		<tr>
			<td class="center" colspan="8">
				No items found
			</td>
		</tr>
	<?php else : ?>
	<?php foreach ($this->items as $i => $item) :
	$rowClass = '';
	if (in_array($item->priority, $blockers)) {
		$rowClass = 'class="warning"';
	} ?>
		<tr <?php echo $rowClass; ?>>
			<td class="center hidden-phone">
				<?php echo (int) $item->id; ?>
			</td>
			<td class="hasContext">
				<div class="hasTooltip" title="<?php echo JHtml::_('string.truncate', $item->description, 100); ?>">
					<?php echo $this->escape($item->title); ?>
				</div>
			</td>
			<td>
				<?php echo (int) $item->priority; ?>
			</td>
			<td>
				<?php echo $item->status; ?>
			</td>
			<td class="hidden-phone">
				N/A
			</td>
			<td class="nowrap small hidden-phone">
				<?php echo JHtml::_('date', $item->opened, 'Y-m-d'); ?>
			</td>
			<td class="nowrap small hidden-phone">
				<?php if ($item->closed != '0000-00-00 00:00:00') : ?>
					<?php echo JHtml::_('date', $item->closed, 'Y-m-d'); ?>
				<?php endif; ?>
			</td>
			<td class="nowrap small hidden-phone">
				<?php if ($item->modified != '0000-00-00 00:00:00') : ?>
					<?php echo JHtml::_('date', $item->modified, 'Y-m-d'); ?>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
	<?php endif; ?>
	</tbody>
</table>
