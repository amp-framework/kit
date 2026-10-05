<?php

declare(strict_types=1);

/** @var \App\View $this */
?>
<ul>
    <template id="item"><li>Wird hochgeladen</li></template>
</ul>
<table>
    <template id="line">
        <tr title="Row"><td aria-label="Cell"><?= $this->t('cell.text'); ?> Cell text</td></tr>
        <template id="nested"><p><?= $this->t('nested.text'); ?> Deep</p></template>
    </template>
</table>
