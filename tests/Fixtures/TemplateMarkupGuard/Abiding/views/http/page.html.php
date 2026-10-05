<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<script type="module" src="<?= $this->escape($this->getVersionedAssetLink('js/app.js')); ?>"></script>
<p class="alert alert--error" role="alert" tabindex="-1" autofocus><?= $this->t($error); ?></p>
<div class="alert alert--error" role="alert" tabindex="-1" autofocus>
    <?= $this->t($error); ?>
</div>
<p class="alert alert--info" role="alert">Something that is no error alert</p>
<section class="big alert--error alert" role="alert" tabindex="-1" autofocus><?= $this->t($error); ?></section>
<p class="alert--errors" role="alert">A class that only holds the name of the error class</p>
<?= '<script>run()</script><style>p {}</style><p style="color: red" onclick="run()">' ?>
<?php echo '<a onclick="run()">'; ?>
