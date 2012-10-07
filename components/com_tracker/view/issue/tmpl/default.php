<?php
/**
 * @package    BabDev.Tracker
 *
 * @copyright  Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

?>
<h3><?php echo $this->item->title; ?></h3>
<div class="container">
	<div class="row-fluid">
		<div class="span4">
			<h4><?php echo JTEXT::_('COM_TRACKER_LABEL_ISSUE_INFO'); ?></h4>
			<strong>
				<?php echo JText::_('COM_TRACKER_HEADING_GITHUB_ID'); ?>
			</strong>
			<a href="https://github.com/joomla/joomla-cms/issues/<?php echo $this->item->gh_id; ?>" target="_blank"><?php echo $this->item->gh_id; ?></a><br />

			<strong>
				<?php echo JText::_('COM_TRACKER_HEADING_PRIORITY'); ?>
			</strong>
			<?php
				if($this->item->priority == 1)
				{
					$status_class = "badge-important";
				}
				elseif($this->item->priority == 2)
				{
					$status_class = "badge-warning";
				}
				elseif($this->item->priority == 3)
				{
					$status_class = "badge-info";
				}
				elseif($this->item->priority == 4)
				{
					$status_class = "badge-inverse";
				}
				elseif($this->item->priority == 5)
				{
					$status_class = "badge";
				}
			?>
			<span class="badge <?php echo $status_class; ?>">
				<?php echo $this->item->priority; ?>
			</span>
			<br />
			<strong>
				<?php echo JTEXT::_('COM_TRACKER_HEADING_DATE_OPENED'); ?>
			</strong>
			<?php echo JHtml::_('date', $this->item->opened, 'DATE_FORMAT_LC4'); ?>
			<br />
			<?php if ($this->item->closed) : ?>
				<strong><?php echo JTEXT::_('COM_TRACKER_HEADING_DATE_CLOSED'); ?></strong>
				<?php echo JHtml::_('date', $this->item->closed, 'DATE_FORMAT_LC4'); ?>
				<br />
			<?php endif; ?>
			<?php if ($this->item->modified != '0000-00-00 00:00:00') : ?>
				<strong><?php echo JTEXT::_('COM_TRACKER_HEADING_DATE_MODIFIED'); ?></strong>
				<?php echo JHtml::_('date', $this->item->modified, 'DATE_FORMAT_LC4'); ?>
			<?php endif; ?>
			<br /><a href="index.php?option=com_tracker&view=issues">Back to Issues</a>
		</div>
		<div class="span8">
			<h4><?php echo JText::_('COM_TRACKER_LABEL_ISSUE_DESC'); ?></h4>
			<div class="well well-small issue">
			<p><?php echo $this->item->description; ?></p>
		</div>
	</div>
</div>
</div>
