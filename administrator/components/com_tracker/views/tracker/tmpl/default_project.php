<?php
/**
 * @package     JTracker
 * @subpackage  com_tracker
 *
 * @copyright   Copyright (C) 2012 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$baseLinkAdd = 'index.php?option=com_categories&view=category&layout=edit&task=category.add&extension=com_tracker'. '.' . $this->project->id;
$buttonStyles = array('class' => 'btn btn-small btn-success');

?>

<div class="row">
	<div class="span4">
		<h2>Categories</h2>
		<?= JHtml::link($baseLinkAdd . '.categories', 'Add a Category', $buttonStyles) ?>
		<div class="well well-small">
			<?= JHtmlCustomfields::getItems('categories', $this->project->id) ? JHtmlProjects::listing('categories', $this->project->id) : 'Use global' ?>
		</div>

	</div>
</div>
<div class="row">
	<div class="span4">
		<h2>Textfields</h2>
		<?= JHtml::link($baseLinkAdd . '.textfields', 'Add a Textfield', $buttonStyles) ?>
		<div class="well well-small">
			<?= JHtmlCustomfields::getItems('textfields', $this->project->id) ? JHtmlProjects::listing('textfields', $this->project->id) : 'Use global' ?>
		</div>

	</div>
	<div class="span4">
		<h2>Selectlists</h2>
		<?= JHtml::link($baseLinkAdd . '.fields', 'Add a Selectlist', $buttonStyles) ?>
		<div class="well well-small">
			<?= JHtmlCustomfields::getItems('fields', $this->project->id) ? JHtmlProjects::listing('fields', $this->project->id) : 'Use global' ?>
		</div>

	</div>
	<div class="span4">
		<h2>Checkboxes</h2>
		<?= JHtml::link($baseLinkAdd . '.checkboxes', 'Add a Checkbox', $buttonStyles) ?>
		<div class="well well-small">
			<?= JHtmlCustomfields::getItems('checkboxes', $this->project->id) ? JHtmlProjects::listing('checkboxes', $this->project->id) : 'Use global' ?>
		</div>

	</div>
</div>

