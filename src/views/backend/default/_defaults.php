<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Shortcode\components\ShortcodeManager;
use Besnovatyj\Shortcode\widgets\ShortcodesList;

/** @var ShortcodeManager $shortcodeManager */

?>
<div class="row">
    <div class="col-12">
        <div class="border border-warning p-3 mb-2">
            <p>Полный список, включая определенные в других местах:</p>
            <div>
                <?= ShortcodesList::widget(); ?>
            </div>
        </div>
    </div>
</div>
