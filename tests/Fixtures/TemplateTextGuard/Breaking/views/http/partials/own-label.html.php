<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<select name="fuel">
    <optgroup label="Fuel"><option value="<?= $this->escape($this->id()); ?>" label="Diesel"><?= $this->t('fuel.diesel'); ?></option></optgroup>
</select>
<div role="textbox" aria-placeholder="Search here" aria-roledescription="Slide"></div>
<div role="slider" aria-valuetext="Half full" aria-label="<?= $this->t('level'); ?>"></div>
