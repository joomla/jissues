<?php
/**
 * User: elkuku
 * Date: 11.10.12
 * Time: 14:22
 */
?>

<div class="row">
    <div class="span12">
        <h2>Categories</h2>
		<?= $this->lists->get('categories') ? : 'Use global' ?>
    </div>
</div>
<div class="row">
    <div class="span4">
        <h2>Textfields</h2>
		<?= $this->lists->get('textfields') ? : 'Use global' ?>
    </div>
    <div class="span4">
        <h2>Selectlists</h2>
		<?= $this->lists->get('fields') ? : 'Use global' ?>
    </div>
    <div class="span4">
        <h2>Checkboxes</h2>
		<?= $this->lists->get('checkboxes') ? : 'Use global' ?>
    </div>
</div>

