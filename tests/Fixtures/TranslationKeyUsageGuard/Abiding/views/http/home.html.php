<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<h1><?= $this->t('app.greeting'); ?></h1>
<?= $this->subRender('http/partials/note.html.php', ['key' => 'app.note']); ?>
