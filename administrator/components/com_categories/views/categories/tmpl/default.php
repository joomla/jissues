<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_categories
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('formbehavior.chosen', 'select');

$user		= JFactory::getUser();
$userId		= $user->get('id');
$extension	= $this->escape($this->state->get('filter.extension'));
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$ordering 	= ($listOrder == 'a.lft');
$saveOrder 	= ($listOrder == 'a.lft' && $listDirn == 'asc');
if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_categories&task=categories.saveOrderAjax&tmpl=component';
	JHtml::_('sortablelist.sortable', 'categoryList', 'adminForm', strtolower($listDirn), $saveOrderingUrl, false, true);
}
$sortFields = $this->getSortFields();
?>
<script type="text/javascript">
	Joomla.orderTable = function() {
		table = document.getElementById("sortTable");
		direction = document.getElementById("directionTable");
		order = table.options[table.selectedIndex].value;
		if (order != '<?php echo $listOrder; ?>') {
			dirn = 'asc';
		} else {
			dirn = direction.options[direction.selectedIndex].value;
		}
		Joomla.tableOrdering(order, dirn, '');
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_categories&view=categories');?>" method="post" name="adminForm" id="adminForm">
<?php if(!empty( $this->sidebar)): ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif;?>
		<div id="filter-bar" class="btn-toolbar">
			<div class="filter-search btn-group pull-left">
				<label for="filter_search" class="element-invisible"><?php echo JText::_('COM_CATEGORIES_ITEMS_SEARCH_FILTER');?></label>
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_CATEGORIES_ITEMS_SEARCH_FILTER'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_CATEGORIES_ITEMS_SEARCH_FILTER'); ?>" />
			</div>
			<div class="btn-group hidden-phone">
				<button class="btn tip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn tip" type="button" onclick="document.id('filter_search').value='';this.form.submit();" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC');?></label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
			<div class="btn-group pull-right hidden-phone">
				<label for="directionTable" class="element-invisible"><?php echo JText::_('JFIELD_ORDERING_DESC');?></label>
				<select name="directionTable" id="directionTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JFIELD_ORDERING_DESC');?></option>
					<option value="asc" <?php if ($listDirn == 'asc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_ASCENDING');?></option>
					<option value="desc" <?php if ($listDirn == 'desc') echo 'selected="selected"'; ?>><?php echo JText::_('JGLOBAL_ORDER_DESCENDING');?></option>
				</select>
			</div>
			<div class="btn-group pull-right">
				<label for="sortTable" class="element-invisible"><?php echo JText::_('JGLOBAL_SORT_BY');?></label>
				<select name="sortTable" id="sortTable" class="input-medium" onchange="Joomla.orderTable()">
					<option value=""><?php echo JText::_('JGLOBAL_SORT_BY');?></option>
					<?php echo JHtml::_('select.options', $sortFields, 'value', 'text', $listOrder);?>
				</select>
			</div>
			<div class="clearfix"></div>
		</div>

		<table class="table table-striped" id="categoryList">
			<thead>
				<tr>
					<th width="1%" class="nowrap center hidden-phone">
						<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'a.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
					</th>
					<th width="1%" class="hidden-phone">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
					<th width="1%" class="nowrap center">
						<?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
					</th>
					<th>
						<?php echo JHtml::_('grid.sort', 'JGLOBAL_TITLE', 'a.title', $listDirn, $listOrder); ?>
					</th>
					<th width="10%" class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ACCESS', 'a.access', $listDirn, $listOrder); ?>
					</th>
					<th width="5%" class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_LANGUAGE', 'language', $this->state->get('list.direction'), $this->state->get('list.ordering')); ?>
					</th>
					<th width="1%" class="nowrap hidden-phone">
						<?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="15">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
				<?php
				$originalOrders = array();
				foreach ($this->items as $i => $item) :
					$orderkey   = array_search($item->id, $this->ordering[$item->parent_id]);
					$canEdit    = $user->authorise('core.edit',       $extension . '.category.' . $item->id);
					$canCheckin = $user->authorise('core.admin',      'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
					$canEditOwn = $user->authorise('core.edit.own',   $extension . '.category.' . $item->id) && $item->created_user_id == $userId;
					$canChange  = $user->authorise('core.edit.state', $extension . '.category.' . $item->id) && $canCheckin;

					// Get the parents of item for sorting
					if ($item->level > 1)
					{
						$parentsStr = "";
						$_currentParentId = $item->parent_id;
						$parentsStr = " ".$_currentParentId;
						for ($i = 0; $i < $item->level; $i++)
						{
							foreach ($this->ordering as $k => $v)
							{
								$v = implode("-", $v);
								$v = "-".$v."-";
								if (strpos($v, "-" . $_currentParentId . "-") !== false)
								{
									$parentsStr .= " ".$k;
									$_currentParentId = $k;
									break;
								}
							}
						}
					}
					else
					{
						$parentsStr = "";
					}
					?>
				<tr class="row<?php echo $i % 2; ?>" sortable-group-id="<?php echo $item->parent_id;?>" item-id="<?php echo $item->id?>" parents="<?php echo $parentsStr?>" level="<?php echo $item->level?>">
					<td class="order nowrap center hidden-phone">
					<?php if ($canChange) :
						$disableClassName = '';
						$disabledLabel    = '';
						if (!$saveOrder) :
							$disabledLabel    = JText::_('JORDERINGDISABLED');
							$disableClassName = 'inactive tip-top';
						endif; ?>
						<span class="sortable-handler hasTooltip <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>">
							<i class="icon-menu"></i>
						</span>

					<?php else : ?>
						<span class="sortable-handler inactive">
							<i class="icon-menu"></i>
						</span>
					<?php endif; ?>
						<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $orderkey + 1;?>" />
					</td>
					<td class="center hidden-phone">
						<?php echo JHtml::_('grid.id', $i, $item->id); ?>
					</td>
					<td class="center">
						<?php echo JHtml::_('jgrid.published', $item->published, $i, 'categories.', $canChange);?>
					</td>
					<td>
						<?php echo str_repeat('<span class="gi">&mdash;</span>', $item->level - 1) ?>
						<?php if ($item->checked_out) : ?>
							<?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'categories.', $canCheckin); ?>
						<?php endif; ?>
						<?php if ($canEdit || $canEditOwn) : ?>
							<a href="<?php echo JRoute::_('index.php?option=com_categories&task=category.edit&id='.$item->id.'&extension='.$extension);?>">
								<?php echo $this->escape($item->title); ?></a>
						<?php else : ?>
							<?php echo $this->escape($item->title); ?>
						<?php endif; ?>
						<span class="small" title="<?php echo $this->escape($item->path);?>">
							<?php if (empty($item->note)) : ?>
								<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS', $this->escape($item->alias));?>
							<?php else : ?>
								<?php echo JText::sprintf('JGLOBAL_LIST_ALIAS_NOTE', $this->escape($item->alias), $this->escape($item->note));?>
							<?php endif; ?>
						</span>
					</td>
					<td class="small hidden-phone">
						<?php echo $this->escape($item->access_level); ?>
					</td>
					<td class="small nowrap hidden-phone">
					<?php if ($item->language == '*'):?>
						<?php echo JText::alt('JALL', 'language'); ?>
					<?php else:?>
						<?php echo $item->language_title ? $this->escape($item->language_title) : JText::_('JUNDEFINED'); ?>
					<?php endif;?>
					</td>
					<td class="center hidden-phone">
						<span title="<?php echo sprintf('%d-%d', $item->lft, $item->rgt);?>">
							<?php echo (int) $item->id; ?></span>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php //Load the batch processing form. ?>
		<?php echo $this->loadTemplate('batch'); ?>

		<input type="hidden" name="extension" value="<?php echo $extension;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<input type="hidden" name="original_order_values" value="<?php echo implode($originalOrders, ','); ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
