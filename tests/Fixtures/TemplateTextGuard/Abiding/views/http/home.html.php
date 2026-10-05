<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<h1><?= $this->t('home.title'); ?></h1>
<p>
    <?= $this->t('home.lead'); ?> *
    <img src="/logo.svg" alt="<?= $this->t('home.logo'); ?>" title="<?= $this->t('home.logo'); ?>">
</p>
<?php if ($this->isOpen()): ?>
<p><?= $this->te('home.open', $this->name()); ?> / <?= $this->t('home.close'); ?></p>
<?php endif; ?>
<ul>
    <template id="row"><li title="<?= $this->t('home.row'); ?>"><?= $this->t('home.row'); ?></li></template>
</ul>
<table>
    <template id="line"><tr title="<?= $this->t('home.line'); ?>"><td aria-label="<?= $this->t('home.cell'); ?>"><?= $this->t('home.cell'); ?></td></tr></template>
</table>
<select name="fuel">
    <optgroup label="<?= $this->t('home.fuel'); ?>"><option value="<?= $this->escape($this->id()); ?>" label="<?= $this->t('home.diesel'); ?>"><?= $this->t('home.diesel'); ?></option></optgroup>
</select>
<div role="slider" aria-valuetext="<?= $this->t('home.level'); ?>" aria-placeholder="<?= $this->t('home.search'); ?>" aria-roledescription="<?= $this->t('home.slide'); ?>"></div>
