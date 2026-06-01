<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Shortcode\entities\Shortcode;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\DetailView;

/* @var $this View */
/* @var $shortcode Shortcode */

$this->title = $shortcode->shortcode;
$this->params['breadcrumbs'][] = ['label' => 'Shortcodes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<p>
    <?= Html::a('Update', ['update', 'id' => $shortcode->id], ['class' => 'btn  btn-primary']) ?>
    <?= Html::a('Delete', ['delete', 'id' => $shortcode->id], [
        'class' => 'btn  btn-danger',
        'data' => [
            'confirm' => 'Are you sure?',
            'method' => 'post',
        ],
    ]) ?>
</p>

<div class="card">
    <div class="card-header">Shortcode</div>
    <div class="card-body table-responsive">
        <?= DetailView::widget([
            'options' => ['class' => 'table detail-view'],
            'model' => $shortcode,
            'attributes' => [
                'id',
                'shortcode',
                'type',
                'replacement',
                'description',
                'example',
            ],
        ]) ?>
    </div>
</div>
