<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var UsersViewResetHtml $this */

defined('_JEXEC') or die;

echo '<h1>Disabled :(</h1>';

return;

JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
?>
<div class="row-fluid">
    <div class="span2">
		<?php echo JHtml::_('sidebar.render'); ?>
    </div>

    <div class="span10 reset <?php echo $this->pageclass_sfx?>">
		<?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
            </h1>
        </div>
		<?php endif; ?>

        <form id="user-registration" action="<?php echo ('index.php?option=com_users&task=resetrequest'); ?>"
              method="post" class="form-validate form-horizontal">

			<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
            <p><?php echo JText::_($fieldset->label); ?></p>

            <fieldset>
				<?php foreach ($this->form->getFieldset($fieldset->name) as $name => $field): ?>
                <div class="control-group">
                    <div class="control-label">
						<?php echo $field->label; ?>
                    </div>
                    <div class="controls">
						<?php echo $field->input; ?>
                    </div>
                </div>
				<?php endforeach; ?>
            </fieldset>
			<?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary validate"><?php echo JText::_('JSUBMIT'); ?></button>
				<?php echo JHtml::_('form.token'); ?>
            </div>
        </form>
    </div>
</div>
