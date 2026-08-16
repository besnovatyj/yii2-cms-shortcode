<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Shortcode\widgets\shortcodesList\ShortcodesList;

?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <?= ShortcodesList::widget([
        'buttonClass' => 'btn btn-outline-info',
        'showModuleLink' => false,
    ]) ?>
    <small class="text-muted">
        Полный список с примерами вставки, включая шорткоды, зарегистрированные в коде.
    </small>
</div>
