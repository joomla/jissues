<?php
/**
 * User: elkuku
 * Date: 12.10.12
 * Time: 08:43
 */

?>

<? foreach ($this->fields as $field) : ?>
<div class="row">
    <div class="span4">
		<?= $field->title ?>
    </div>
    <div class="span8">
		<?= $this->fieldList[$field->alias] ?>
    </div>
</div>
<? endforeach; ?>
