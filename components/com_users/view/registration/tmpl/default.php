<?php
/**
 * @package     JTracker
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var UsersViewRegistrationHtml $this */

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

    <div class="span10 registration<?php echo $this->pageclass_sfx?>">
		<?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1><?php echo $this->escape($this->params->get('page_heading')); ?></h1>
        </div>
		<?php endif; ?>

        <form id="member-registration" action="<?php echo ('index.php'); ?>" method="post"
              class="form-validate form-horizontal">
			<?php foreach ($this->form->getFieldsets() as $fieldset): // Iterate through the form fieldsets and display each one.?>
			<?php $fields = $this->form->getFieldset($fieldset->name); ?>
			<?php if (count($fields)): ?>
                <fieldset>
					<?php if (isset($fieldset->label)): // If the fieldset has a label set, display it as the legend.
					?>
                    <legend><?php echo JText::_($fieldset->label);?></legend>
					<?php endif;?>
					<?php foreach ($fields as $field): // Iterate through the fields in the set and display them.?>
					<?php if ($field->hidden): // If the field is hidden, just display the input.?>
						<?php echo $field->input; ?>
						<?php else: ?>
                        <div class="control-group">
                            <div class="control-label">
								<?php echo $field->label; ?>
								<?php if (!$field->required && $field->type != 'Spacer'): ?>
                                <span class="optional"><?php echo JText::_('COM_USERS_OPTIONAL');?></span>
								<?php endif; ?>
                            </div>
                            <div class="controls">
								<?php echo $field->input;?>
                            </div>
                        </div>
						<?php endif; ?>
					<?php endforeach;?>
                </fieldset>
				<?php endif; ?>
			<?php endforeach;?>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary validate"><?php echo JText::_('JREGISTER');?></button>
                <a class="btn" href="index.php"
                   title="<?php echo JText::_('JCANCEL');?>"><?php echo JText::_('JCANCEL');?></a>
                <input type="hidden" name="option" value="com_users"/>
                <input type="hidden" name="task" value="register"/>
				<?php echo JHtml::_('form.token');?>
            </div>
        </form>
    </div>
</div>
