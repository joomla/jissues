<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var UsersViewProfileHtml $this */

defined('_JEXEC') or die;

JHtml::_('behavior.tooltip');

JLoader::register('JHtmlUsers', JPATH_COMPONENT . '/html/users.php');

?>
<div class="row-fluid">
    <div class="span2">
		<?php echo JHtml::_('sidebar.render'); ?>
    </div>
    <div class="span10 profile <?php echo $this->pageclass_sfx?>">

		<?php if (JFactory::getUser()->id == $this->data->id) : ?>
        <ul class="btn-toolbar pull-right">
            <li class="btn-group">
                <a class="btn"
                   href="<?php echo ('index.php?option=com_users&task=profile&user_id=' . (int) $this->data->id);?>">
                    <span class="icon-user"></span> <?php echo JText::_('COM_USERS_EDIT_PROFILE'); ?></a>
            </li>
        </ul>
		<?php endif; ?>
		<?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
            </h1>
        </div>
		<?php endif; ?>

		<?php include $this->getPath('default_core'); ?>

		<?php include $this->getPath('default_params'); ?>

		<?php include $this->getPath('default_custom'); ?>

    </div>
</div>
