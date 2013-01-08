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

// @todo move the "template" to ... a template ¿
$template = "
Please enter a description of the issue

## Test instructions

Please add some instructions on how to reproduce the issue

Example:

* Install Joomla! with sample data
* Browse to the backend and click...

## Test code
You may add some code if needed

```php
echo 'my issue';
```

## Screenshots
You may upload screenshots or reference external images here.

Example:

![Joomla! World Conference](http://conference.joomla.org/images/banners/general/728x90.jpg)
";

?>
<h1>Add a new Issue</h1>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm" class="form form-horizontal">

	<!-- @todo project id selector or value ? -->
	<input type="hidden" name="project_id" value="<?= $this->project->id ?>">

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
			<?php foreach (JHtmlCustomfields::items('fields', $this->project->id) as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="select-<?=$field->id?>"><?= $field->title ?></label>

                <div class="controls">
					<?= JHtmlCustomfields::select('fields.' . $field->id, $this->project->id, $field->id) ?>
                </div>
            </div>
			<?php endforeach; ?>

        </div>

        <div class="span6">

			<?php foreach (JHtmlCustomfields::items('textfields', $this->project->id) as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="txt-<?=$field->id?>"><?= $field->title ?></label>

                <div class="controls">
					<?= JHtmlCustomfields::textfield($field->id, '', $field->description) ?>
                </div>
            </div>
			<?php endforeach; ?>

	        <?php foreach (JHtmlCustomfields::items('checkboxes', $this->project->id) as $field) : ?>
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
