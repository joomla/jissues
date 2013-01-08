<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

/* @var TrackerViewAddHtml $this */

defined('_JEXEC') or die;

JHtmlBootstrap::tooltip();

$template = file_get_contents(__DIR__ . '/new_issue_template.md');

?>
<h1>Add a new Issue</h1>

<h2><?= $this->project->title ?></h2>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal">

	<!-- @todo project id selector or value ? -->
	<input type="hidden" name="jform[project_id]" value="<?= $this->project->project_id ?>">

	<h3>1) Summary</h3>

    <div class="well well-small">
        <input style="font-size: 1.5em;" name="jform[title]" id="jform_title" type="text" class="span12" />
    </div>

    <h3>2) Description.</h3>

    <div class="description">
		<?php echo $this->editor->display('jform[description]', $template, '100%', 300, 10, 10, false, 'jform_description', null, null, $this->editorParams); ?>
    </div>

    <h3>3) Technical details.</h3>

    <div class="row">
        <div class="span6">

	        <!-- Select lists ! -->
			<?php foreach (JHtmlCustomfields::items('fields', $this->project->project_id) as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="select-<?=$field->id?>"><?= $field->title ?></label>

                <div class="controls">
					<?= JHtmlCustomfields::select('fields.' . $field->id, $this->project->project_id, $field->id) ?>
                </div>
            </div>
			<?php endforeach; ?>

        </div>

        <div class="span6">

			<?php foreach (JHtmlCustomfields::items('textfields', $this->project->project_id) as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="txt-<?=$field->id?>"><?= $field->title ?></label>

                <div class="controls">
					<?= JHtmlCustomfields::textfield($field->id, '', $field->description) ?>
                </div>
            </div>
			<?php endforeach; ?>

	        <?php foreach (JHtmlCustomfields::items('checkboxes', $this->project->project_id) as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="chk-<?=$field->id?>"><?= $field->title ?></label>

                <div class="controls">
			        <?= JHtmlCustomfields::checkbox($field->id, '', $field->description) ?>
                </div>
            </div>
	        <?php endforeach; ?>

        </div>
    </div>

    <h3>4) Notifications (@todo)</h3>

    <div class="row well well-small">
        <div class="span6">
            <input type="checkbox" value="email_notify" id="email-notify" checked="checked"/>
            <label for="email-notify"> Send me news about this issue via e-mail</label>
        </div>

        <div class="span6 center">
            <input class="btn btn-large btn-success" type="submit" value="Submit the Issue Report"/>
        </div>
    </div>

	<input type="hidden" name="task" value="save" />
	<?php echo JHtml::_('form.token'); ?>
</form>
