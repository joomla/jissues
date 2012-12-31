<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/* @var TrackerViewTracker $this */

$baseLinkAdd = 'index.php?option=com_categories&view=category&layout=edit&task=category.add&extension=com_tracker';
$buttonStyles = array('class' => 'btn btn-small btn-success');
JHtmlBootstrap::tooltip();
?>

<div class="row-fluid">
    <div class="span2"><?= JHtml::_('sidebar.render') ?></div>

    <div class="span10">
        <form class="form" name="adminForm" id="adminForm" method="post">
            <div class="row">
                <div class="span12 well well-small">
					<?= JHtmlprojects::select('com_tracker', 'project', $this->project, JText::_('Select a Project')); ?>
                    <span style="color: orange; font-size: 1.5em; cursor: help;" class="hasTooltip"
                          title="Select a project to define project specific items."><span class="icon-comment"></span></span>
                </div>
            </div>

			<?php if ($this->project) : ?>

			<?= $this->loadTemplate('project') ?>

			<?php else : ?>
            <div class="row-fluid">
                <div class="span6">
                    <h2>Projects</h2>
					<?= JHtml::link($baseLinkAdd, 'Add a Project', $buttonStyles) ?>
                    <div class="well well-small">
						<?= JHtmlProjects::listing('com_tracker') ?>
                    </div>
                </div>

                <div class="span6">
                    <h2>Categories</h2>
					<?= JHtml::link($baseLinkAdd . '.categories', 'Add a Category', $buttonStyles) ?>
                    <div class="well well-small">
						<?= JHtmlProjects::listing('com_tracker.categories') ?>
                    </div>
                </div>
            </div>

            <h2>Global fields</h2>
            <div class="row-fluid">
                <div class="span4">
                    <h3>Textfields</h3>
					<?= JHtml::link($baseLinkAdd . '.textfields', 'Add a Textfield', $buttonStyles) ?>
                    <div class="well well-small">
						<?= JHtmlProjects::listing('com_tracker.textfields') ?>
                    </div>
                </div>

                <div class="span4">
                    <h3>Selectlists</h3>
					<?= JHtml::link($baseLinkAdd . '.fields', 'Add a Selectlist', $buttonStyles) ?>
                    <div class="well well-small">
						<?= JHtmlProjects::listing('com_tracker.fields', true) ?>
                    </div>
                </div>

                <div class="span4">
                    <h3>Checkboxes</h3>
					<?= JHtml::link($baseLinkAdd . '.checkboxes', 'Add a Checkbox', $buttonStyles) ?>
                    <div class="well well-small">
						<?= JHtmlProjects::listing('com_tracker.checkboxes') ?>
                    </div>
                </div>
            </div>
			<?php endif; ?>

            <div>
                <input type="hidden" name="option" value="com_tracker"/>
            </div>

        </form>
    </div>
</div>
