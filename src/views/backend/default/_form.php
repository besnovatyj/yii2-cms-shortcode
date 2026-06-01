<?php


/*
 * Copyright (c) 2026 Besnovatyj. Licensed under the MIT License.
 */

use Besnovatyj\Shortcode\forms\backend\ShortcodeForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\web\View;

/* @var $this View */
/* @var $model ShortcodeForm */
/* @var $form ActiveForm */

?>
<?php $form = ActiveForm::begin(); ?>
<div class="card">
    <div class="card-header">Common</div>
    <div class="card-body">
        <?= $form->field($model, 'type')->dropDownList($model->typesList(), ['prompt' => 'Не выбрано', 'class' => 'custom-select']) ?>
        <?= $form->field($model, 'shortcode')->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
        <?= $form->field($model, 'replacement')->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
        <?= $form->field($model, 'description')->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
        <?= $form->field($model, 'example')->textInput(['maxlength' => true, 'class' => 'form-control']) ?>
    </div>
    <div class="card-footer">
        <div class="form-group">
            <div class="d-grid">
                <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
