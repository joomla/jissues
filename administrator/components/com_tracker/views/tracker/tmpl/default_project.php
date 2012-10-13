<?php
/**
 * User: elkuku
 * Date: 11.10.12
 * Time: 14:22
 */
?>

<div class="row">
    <div class="span6">
        <h3>Categories</h3>
		<?= $this->lists->get('categories') ? : 'Use global' ?>
    </div>
    <div class="span6">
        <h3>Fields</h3>
	    <?= $this->lists->get('fields') ? : 'Use global' ?>
    </div>
</div>

