<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Backend\Widgets\grid\ActionColumn;
use Besnovatyj\Shortcode\entities\Shortcode;
use Besnovatyj\Shortcode\forms\search\ShortcodeSearch;
use modules\user\components\Helper;
use yii\bootstrap5\Html;
use Besnovatyj\Backend\Widgets\pagination\LinkPager;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\StringHelper;
use yii\web\View;

/* @var $this View */
/* @var $searchModel ShortcodeSearch */
/* @var $dataProvider ActiveDataProvider */

$this->title = 'Shortcodes';
$this->params['breadcrumbs'][] = $this->title;
?>

<p>
    <?= Html::a('Create', ['create'], ['class' => 'btn  btn-success']) ?>
</p>

<div class="container-fluid">
    <?= $this->render('_defaults') ?>

    <div class="card">
        <div class="card-header"><?= $this->title ?></div>
        <div class="card-body table-responsive">
            <?= GridView::widget([
                'options' => ['class' => 'table detail-view'],
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => "{summary}\n{items}",
                'columns' => [
                    [
                        'attribute' => 'shortcode',
                        'value' => function (Shortcode $model) {
                            return Html::a(Html::encode($model->shortcode), ['update', 'id' => $model->id]);
                        },
                        'format' => 'raw',
                    ],
                    [
                        'attribute' => 'type',
                        'value' => static function (Shortcode $entity) {
                            return StringHelper::mb_ucfirst($entity->type);
                        },
                        'filter' => $searchModel::typesList(),
                        'format' => 'raw',
                    ],
                    'replacement',
                    [
                        'class' => ActionColumn::class,
                        'template' => Helper::filterActionColumn(['view', 'update', 'delete']),
                    ],
                ],
            ]) ?>
        </div>
        <div class="card-footer clearfix">
            <nav aria-label="" class="nav-pagination">
                <?= LinkPager::widget([
                    'pagination' => $dataProvider->getPagination(),
                ]) ?>
            </nav>
        </div>
    </div>
