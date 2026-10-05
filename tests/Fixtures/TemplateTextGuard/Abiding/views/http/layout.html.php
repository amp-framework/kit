<?php

declare(strict_types=1);

/** @var \App\View $this */
/** @var string $content the page, already rendered */
?>
<!DOCTYPE html>
<html lang="<?= $this->escape($this->getHtmlLanguage()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= $this->t('layout.description'); ?>">
    <title><?= $this->t('layout.title'); ?></title>
</head>
<body>
    <header>
        <a href="/" aria-label="<?= $this->t('layout.home'); ?>">Note<span>Book</span></a>
        <nav>
            <a href="/a"><?= $this->t('nav.a'); ?></a> | <a href="/b"><?= $this->t('nav.b'); ?></a>
        </nav>
    </header>
    <main><?= $content; ?></main>
</body>
</html>
