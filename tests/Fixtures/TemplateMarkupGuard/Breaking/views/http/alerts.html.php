<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<p class="alert alert--error" role="alert"><?= $this->t($first); ?></p>
<div class="alert alert--error" role="alert" tabindex="-1"><?= $this->t($second); ?></div>
<p class="alert alert--error" role="alert" tabindex="-1" autofocus><?= $this->t($third); ?></p>
<section class="alert--error alert" role="alert"><?= $this->t($fourth); ?></section>
<p class='alert alert--error big' role="alert" tabindex="-1"><?= $this->t($fifth); ?></p>
<p class="alert--errors" role="alert">Something that is no error alert</p>
