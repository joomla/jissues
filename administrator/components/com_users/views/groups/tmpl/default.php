<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');

$user		= JFactory::getUser();
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));

JText::script('COM_USERS_GROUPS_CONFIRM_DELETE');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'groups.delete')
		{
			var f = document.adminForm;
			var cb='';
<?php foreach ($this->items as $i => $item):?>
<?php if ($item->user_count > 0):?>
			cb = f['cb'+<?php echo $i;?>];
			if (cb && cb.checked) {
				if (confirm(Joomla.JText._('COM_USERS_GROUPS_CONFIRM_DELETE'))) {
					Joomla.submitform(task);
				}
				return;
			}
<?php endif;?>
<?php endforeach;?>
		}
		Joomla.submitform(task);
	}
</script>
<form action="<?php echo JRoute::_('index.php?option=com_users&view=groups');?>" method="post" name="adminForm" id="adminForm">
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
				<input type="text" name="filter_search" placeholder="<?php echo JText::_('COM_USERS_SEARCH_GROUPS_LABEL'); ?>" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_USERS_SEARCH_IN_GROUPS'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button class="btn tip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn tip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
		</div>
		<div class="clearfix"> </div>

		<table class="table table-striped">
			<thead>
				<tr>
					<th width="1%">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
					<th class="left">
						<?php echo JText::_('COM_USERS_HEADING_GROUP_TITLE'); ?>
					</th>
					<th width="20%">
						<?php echo JText::_('COM_USERS_HEADING_USERS_IN_GROUP'); ?>
					</th>
					<th width="5%">
						<?php echo JText::_('JGRID_HEADING_ID'); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php foreach ($this->items as $i => $item) :
				$canCreate = $user->authorise('core.create', 'com_users');
				$canEdit   = $user->authorise('core.edit',    'com_users');

				// If this group is super admin and this user is not super admin, $canEdit is false
				if (!$user->authorise('core.admin') && (JAccess::checkGroup($item->id, 'core.admin')))
				{
					$canEdit = false;
				}
				$canChange	= $user->authorise('core.edit.state',	'com_users');
			?>
				<tr class="row<?php echo $i % 2; ?>">
					<td class="center">
						<?php if ($canEdit) : ?>
							<?php echo JHtml::_('grid.id', $i, $item->id); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php echo str_repeat('<span class="gi">|&mdash;</span>', $item->level) ?>
						<?php if ($canEdit) : ?>
						<a href="<?php echo JRoute::_('index.php?option=com_users&task=group.edit&id='.$item->id);?>">
							<?php echo $this->escape($item->title); ?></a>
						<?php else : ?>
							<?php echo $this->escape($item->title); ?>
						<?php endif; ?>
						<?php if (JDEBUG) : ?>
							<div class="small"><a href="<?php echo JRoute::_('index.php?option=com_users&view=debuggroup&group_id='.(int) $item->id);?>">
							<?php echo JText::_('COM_USERS_DEBUG_GROUP');?></a></div>
						<?php endif; ?>
					</td>
					<td class="center">
						<?php echo $item->user_count ? $item->user_count : ''; ?>
					</td>
					<td class="center">
						<?php echo (int) $item->id; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
