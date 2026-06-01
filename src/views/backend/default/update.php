<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use yii\web\View;

/* @var $this View */
/* @var $model ShortcodeForm */
/* @var $shortcode Shortcode */

$this->title = 'Update Shortcode: ' . $shortcode->shortcode;
$this->params['breadcrumbs'][] = ['label' => 'Shortcodes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $shortcode->shortcode, 'url' => ['view', 'id' => $shortcode->id]];
$this->params['breadcrumbs'][] ='Update';
?>

<?= $this->render('_defaults') ?>

<div class="shortcode-update">
    <?= $this->render('_form', [
        'model' => $model,
        'shortcode' => $shortcode,
    ]) ?>
</div>
