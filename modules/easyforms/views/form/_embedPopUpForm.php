<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\slider\Slider;
use kartik\select2\Select2;
use kartik\color\ColorInput;

/* @var $this yii\web\View */
/* @var $formModel app\models\Form */
/* @var $formDataModel app\models\FormData */
/* @var $popupForm app\models\forms\PopupForm */

?>
<style>
    .form-group {
        margin-bottom: 25px;
    }

    .form-group .slider.slider-horizontal {
        width: 100%;
    }

    .form-group .slider-container {
        margin: 5px 0;
    }
</style>

<div class="row">
    <?php $form = ActiveForm::begin([
        'id' => 'popup-form',
        'action' => ['form/popup-preview', 'id' => $formModel->id],
        'options' => [
            'class' => 'form-vertical',
            'target' => '_blank',
        ],
    ]) ?>
    <div class="col-sm-4">
        <?php $popupForm->button_text = 'Open Pop-Up Form'; ?>
        <?= $form->field($popupForm, 'button_text') ?>
    </div>
    <div class="col-sm-4">
        <?php $popupForm->button_placement = 'inline'; ?>
        <?= $form->field($popupForm, 'button_placement')->widget(Select2::classname(), [
            'data' => [
                'inline' => Yii::t('form', 'Inline'),
                'right' => Yii::t('form', 'Right'),
                'left' => Yii::t('form', 'Left'),
                'bottom' => Yii::t('form', 'Bottom'),
            ],
            'options' => ['placeholder' => 'Select a placement...'],
            'pluginOptions' => [
                'allowClear' => false
            ],
        ]) ?>
    </div>
    <div class="col-sm-4">
        <?php $popupForm->button_color = 'default'; ?>
        <?= $form->field($popupForm, 'button_color')->widget(Select2::classname(), [
            'data' => [
                'default' => Yii::t('form', 'Default'),
                'success' => Yii::t('form', 'Success'),
                'error' => Yii::t('form', 'Error'),
                'warning' => Yii::t('form', 'Warning'),
                'secondary' => Yii::t('form', 'Secondary'),
            ],
            'options' => ['placeholder' => 'Select a color theme...'],
            'pluginOptions' => [
                'allowClear' => false
            ],
        ]) ?>
    </div>

    <div class="col-sm-6">
        <?php $popupForm->popup_margin = 60; ?>
        <?= $form->field($popupForm, 'popup_margin')->widget(Slider::classname(), [
            'pluginOptions' => [
                'min' => 0,
                'max' => 120,
                'step' => 1,
                'formatter' => new yii\web\JsExpression("function(val) { 
                    return val + 'px';
                }"),
            ]
        ]) ?>
    </div>
    <div class="col-sm-6">
        <?php $popupForm->popup_padding = 20; ?>
        <?= $form->field($popupForm, 'popup_padding')->widget(Slider::classname(), [
            'pluginOptions' => [
                'min' => 0,
                'max' => 60,
                'step' => 1,
                'formatter' => new yii\web\JsExpression("function(val) { 
                    return val + 'px';
                }"),
            ]
        ]) ?>
    </div>
    <div class="col-sm-6">
        <?php $popupForm->popup_width = 60; ?>
        <?= $form->field($popupForm, 'popup_width')->widget(Slider::classname(), [
            'pluginOptions' => [
                'min' => 20,
                'max' => 90,
                'step' => 1,
                'formatter' => new yii\web\JsExpression("function(val) { 
                    return val + '%';
                }"),
            ]
        ]) ?>
    </div>
    <div class="col-sm-6">
        <?php $popupForm->popup_radius = 10; ?>
        <?= $form->field($popupForm, 'popup_radius')->widget(Slider::classname(), [
            'pluginOptions' => [
                'min' => 0,
                'max' => 60,
                'step' => 1,
                'formatter' => new yii\web\JsExpression("function(val) { 
                    return val + 'px';
                }"),
            ]
        ]) ?>
    </div>

    <div class="col-sm-6">
        <?php $popupForm->animation_effect = 'fade-in'; ?>
        <?= $form->field($popupForm, 'animation_effect')->widget(Select2::classname(), [
            'data' => [
                'fade-in' => Yii::t('form', 'Fade In'),
                'fade-in-scale' => Yii::t('form', 'Fade In & Scale'),
                'slide-in-top' => Yii::t('form', 'Slide In Top'),
                'slide-in-right' => Yii::t('form', 'Slide In Right'),
                'slide-in-bottom' => Yii::t('form', 'Slide In Bottom'),
                'slide-in-left' => Yii::t('form', 'Slide In Left'),
            ],
            'options' => ['placeholder' => Yii::t('form', 'Select an effect...')],
            'pluginOptions' => [
                'allowClear' => false
            ],
        ]) ?>
    </div>
    <div class="col-sm-6">
        <?php $popupForm->animation_duration = '0.6'; ?>
        <?= $form->field($popupForm, 'animation_duration')->widget(Select2::classname(), [
            'data' => [
                '0.3' => 'Fast',
                '0.6' => 'Normal',
                '0.9' => 'Slow',
            ],
            'options' => ['placeholder' => Yii::t('form', 'Select a duration...')],
            'pluginOptions' => [
                'allowClear' => false
            ],
        ]) ?>
    </div>

    <div class="col-sm-6">
        <?php $popupForm->popup_color = 'rgb(255, 255, 255)'; ?>
        <?= $form->field($popupForm, 'popup_color')->widget(ColorInput::classname(), [
            'value' => 'rgb(255, 255, 255)',
            'options' => ['placeholder' => Yii::t('form', 'Choose your color...')],
            'pluginOptions' => [
                'showInput' => false,
                'preferredFormat' => 'rgb'
            ]
        ]) ?>
    </div>
    <div class="col-sm-6">
        <?php $popupForm->overlay_color = 'rgba(0, 0, 0, 0.75)'; ?>
        <?= $form->field($popupForm, 'overlay_color')->widget(ColorInput::classname(), [
            'options' => ['placeholder' => Yii::t('form', 'Choose your color...')],
            'pluginOptions' => [
                'showInput' => false,
                'preferredFormat' => 'rgb'
            ]
        ]) ?>
    </div>
    <div class="col-sm-12">
        <?= Html::submitButton(
            Yii::t('form', 'Preview'),
            [
                'class' => 'btn btn-default',
                'name' => 'preview',
            ]
        ) ?>
        <button type="button" id="generateCode" class="btn btn-primary" data-toggle="modal"
                data-target="#generatedCodeModal">
            <i class="glyphicon glyphicon-ok"></i> <?= Yii::t('form', 'Generate Code') ?>
        </button>
    </div>
    <?php ActiveForm::end() ?>
</div>

<!-- Modal -->
<div class="modal fade" id="generatedCodeModal" tabindex="-1" role="dialog" aria-labelledby="generatedCodeLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="generatedCodeLabel"><?= Yii::t('form', 'Embed Pop-Up Form') ?></h4>
            </div>
            <div class="modal-body">
                <p><?= Yii::t('form', 'The easiest way to create a popup window and embed the Form into it is to do so using the following code.') ?></p>
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?= Yii::t("app", "Embed with design:") ?></h3>
                    </div>
                    <div class="panel-body">
                        <form>
                            <textarea id="generatedCode" class="form-control" rows="6" onfocus="this.select();"
                                      onmouseup="return false;"><?= Yii::t('form', 'Loading...') ?></textarea>
                        </form>
                    </div>
                    <div class="panel-footer">
                        <p class="hint-block">
                            <?= Yii::t('form', 'Remember always between the opening and closing &lt;body&gt; tag.') ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default"
                        data-dismiss="modal"><?= Yii::t('form', 'Close') ?></button>
            </div>
        </div>
    </div>
</div>
