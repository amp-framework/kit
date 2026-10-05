<?php

declare(strict_types=1);

/** @var \App\View $this */
/** @var string $placeholder */
/** @var string $value */
?>
<input class="input" name="q" placeholder="<?= $this->t($placeholder); ?>" value="<?= $this->escape($value); ?>">
