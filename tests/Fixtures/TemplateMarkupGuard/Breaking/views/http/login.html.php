<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<form method="post">
    <p class="alert alert--error" role="alert" tabindex="-1" autofocus><?= $this->t($error); ?></p>
    <input name="password" type="password" autofocus>
</form>
