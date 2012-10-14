<?php
/**
 * User: elkuku
 * Date: 10.10.12
 * Time: 18:35
 */

/* @var TrackerViewTracker $this */
?>

<div class="row-fluid">
    <div class="span2"><?= JHtml::_('sidebar.render') ?></div>

    <div class="span10">
        <form class="form" name="adminForm" id="adminForm" method="post">
            <div class="row">
                <div class="span12">
					<?= JHtmlprojects::select('com_tracker', 'project', $this->project, JText::_('Select a Project')); ?>
                </div>
            </div>

			<?php if ($this->project) : ?>
				<?= $this->loadTemplate('project'); ?>
			<?php else : ?>
	            <h2>Global fields</h2>
				<?= JHtmlProjects::listing('com_tracker.fields', true) ?>
			<?php endif; ?>

            <div>
                <input type="hidden" name="option" value="com_tracker"/>
            </div>

        </form>
    </div>
</div>
