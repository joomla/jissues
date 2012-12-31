<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 Open Source Matters, Inc. All rights reserved.
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
    <h3>1) Summary</h3>

    <div class="well well-small">
        <input style="font-size: 1.5em;" name="jform[title]" id="jform_title" type="text" class="span12" />
    </div>

    <h3>2) Description.</h3>

    <div class="description">
		<?php echo $this->editor->display('jform[description]', $template, '100%', 300, 10, 10, false, 'jform_description', null, null, $this->editorParams); ?>
    </div>

    <h3>3) Category</h3>

    <div class="row">
        <div class="span12">
			<?= $this->lists->get('categories') ?>

            @todo Some more info about categories

        </div>
    </div>

    <h3>4) Technical details.</h3>

    <div class="row">
        <div class="span6">

			<?php foreach ($this->lists->get('selects') as $select) : ?>
            <div class="control-group">
                <label class="control-label" for="select-<?=$select->alias?>"><?= $select->title ?></label>

                <div class="controls">
					<?= JHtmlProjects::select('com_tracker.fields.' . $select->id, $select->alias) ?>
                </div>
            </div>
			<?php endforeach; ?>

        </div>

        <div class="span6">

			<?php foreach ($this->lists->get('textfields') as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="txt-<?=$field->alias?>"><?= $field->title ?></label>

                <div class="controls">
					<?= JHtmlProjects::textfield($field->alias, '', $field->description) ?>
                </div>
            </div>
			<?php endforeach; ?>

	        <?php foreach ($this->lists->get('checkboxes') as $field) : ?>
            <div class="control-group">
                <label class="control-label" for="chk-<?=$field->alias?>"><?= $field->title ?></label>

                <div class="controls">
			        <?= JHtmlProjects::checkbox($field->alias, '', $field->description) ?>
                </div>
            </div>
	        <?php endforeach; ?>

        </div>
    </div>

    <h3>5) Notifications</h3>

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
