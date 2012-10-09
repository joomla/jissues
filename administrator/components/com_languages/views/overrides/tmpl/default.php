<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
JHtml::_('bootstrap.tooltip');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
$client    = $this->state->get('filter.client') == 'site' ? JText::_('JSITE') : JText::_('JADMINISTRATOR');
$language  = $this->state->get('filter.language');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction')); ?>
<form action="<?php echo JRoute::_('index.php?option=com_languages&view=overrides'); ?>" method="post" name="adminForm" id="adminForm">
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
				<input type="text" name="filter_search" id="filter_search" placeholder="<?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDES_FILTER_SEARCH_DESC'); ?>" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_LANGUAGES_VIEW_OVERRIDES_FILTER_SEARCH_DESC'); ?>" />
			</div>
			<div class="btn-group pull-left">
				<button class="btn hasTooltip" type="submit" title="<?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
				<button class="btn hasTooltip" type="button" title="<?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?>" onclick="document.id('filter_search').value='';this.form.submit();"><i class="icon-remove"></i></button>
			</div>
		</div>
		<div class="clearfix"></div>

		<table class="adminlist">
			<thead>
				<tr>
					<th width="1%">
						<input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
					</th>
					<th width="30%" class="left">
						<?php echo JHtml::_('grid.sort', 'COM_LANGUAGES_VIEW_OVERRIDES_KEY', 'key', $listDirn, $listOrder); ?>
					</th>
					<th class="left">
						<?php echo JHtml::_('grid.sort', 'COM_LANGUAGES_VIEW_OVERRIDES_TEXT', 'text', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap">
						<?php echo JText::_('COM_LANGUAGES_FIELD_LANG_TAG_LABEL'); ?>
					</th>
					<th>
						<?php echo JText::_('JCLIENT'); ?>
					</th>
					<th class="right" width="20">
						<?php echo JText::_('COM_LANGUAGES_HEADING_NUM'); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="6">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php $canEdit = JFactory::getUser()->authorise('core.edit', 'com_languages');
			$i = 0;
			foreach($this->items as $key => $text): ?>
				<tr class="row<?php echo $i % 2; ?>" id="overriderrow<?php echo $i; ?>">
					<td class="center">
						<?php echo JHtml::_('grid.id', $i, $key); ?>
					</td>
					<td>
						<?php if ($canEdit): ?>
							<a id="key[<?php echo $this->escape($key); ?>]" href="<?php echo JRoute::_('index.php?option=com_languages&task=override.edit&id='.$key); ?>"><?php echo $this->escape($key); ?></a>
						<?php else: ?>
							<?php echo $this->escape($key); ?>
						<?php endif; ?>
					</td>
					<td>
						<span id="string[<?php	echo $this->escape($key); ?>]"><?php echo $this->escape($text); ?></span>
					</td>
					<td class="center">
						<?php echo $language; ?>
					</td>
					<td class="center">
						<?php echo $client; ?>
					</td>
					<td class="right">
						<?php echo $this->pagination->getRowOffset($i); ?>
					</td>
				</tr>
			<?php $i++;
			endforeach; ?>
			</tbody>
		</table>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
		<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
