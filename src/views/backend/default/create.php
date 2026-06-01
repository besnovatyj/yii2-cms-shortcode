<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use yii\web\View;

/* @var $this View */
/* @var $model ShortcodeForm */

$this->title = 'Create Shortcode';
$this->params['breadcrumbs'][] = ['label' => 'Shortcodes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<?= $this->render('_defaults') ?>

<div class="shortcode-create">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
