<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<form method="post">
<?php if ($error !== null): ?>
    <p class="alert alert--error" role="alert"><?= $this->t($error); ?></p>
<?php endif; ?>
    <input name="password" type="password" autofocus>
</form>
