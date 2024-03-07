<?php

use kartik\file\FileInput;
use yii\helpers\Html;
use kartik\form\ActiveForm;
use kartik\switchinput\SwitchInput;
use kartik\select2\Select2;

/** @var \app\modules\user\models\Role $role */
//$role = Yii::$app->getModule("user")->model("Role");
$logo = Yii::$app->formsettings->get('app.logo');

$this->title = Yii::t('form', 'Site settings');
$this->params['breadcrumbs'][] = ['label' => $this->title];

?>
    <div class="account-management">

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <i class="glyphicon glyphicon-cogwheels" style="margin-right: 5px;"></i>
                    <?= Html::encode($this->title) ?>
                </h3>
            </div>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
                <div class='row'>
                    <div class='col-sm-6'>
                        <div class="form-group">
                            <?= Html::label(Yii::t("app", "Name"), 'app.name', ['class' => 'control-label']) ?>
                            <?= Html::textInput('app_name', Yii::$app->formsettings->get('app.name'), ['class' => 'form-control']) ?>
                        </div>
                    </div>
                    <div class='col-sm-6'>
                        <div class="form-group">
                            <div class="form-group">
                                <?= Html::label(Yii::t("app", "Logo"), 'app.name', ['class' => 'control-label']) ?>
                                <?php $removeLink = !empty($logo) ? '<a href="#" class="file-caption-remove text-muted pull-right"><span class="glyphicon glyphicon-remove"></span></a>' : ''; ?>
                                <?= FileInput::widget([
                                    'name' => 'logo',
                                    'options' => ['accept' => 'image/*'],
                                    'pluginOptions' => [
                                        'showPreview' => false,
                                        'showCaption' => true,
                                        'showRemove' => true,
                                        'showUpload' => false,
                                        'showCancel' => true,
                                        'initialCaption' => basename($logo),
                                        'layoutTemplates' => [
                                            'caption' => "<div class='file-caption form-control {class}' tabindex='500'>
                                                        <span class='file-caption-icon'></span>
                                                        <input class='file-caption-name' style='width: 85%' onkeydown='return false;' onpaste='return false;'>
                                                        {$removeLink}
                                                      </div>",
                                        ],
                                    ]
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class='col-sm-12'>
                        <div class="form-group">
                            <?= Html::label(Yii::t("app", "Description"), 'app_description', ['class' => 'control-label']) ?>
                            <?= Html::textarea('app_description', Yii::$app->formsettings->get('app.description'), ['class' => 'form-control']) ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <?= Html::label(Yii::t("app", "Admin e-mail"), 'app_adminEmail', ['class' => 'control-label']) ?>
                            <?= Html::textInput('app_adminEmail', Yii::$app->formsettings->get('app.adminEmail'), ['class' => 'form-control']) ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <?= Html::label(Yii::t("app", "Support e-mail"), 'app_supportEmail', ['class' => 'control-label']) ?>
                            <?= Html::textInput('app_supportEmail', Yii::$app->formsettings->get('app.supportEmail'), ['class' => 'form-control']) ?>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <?= Html::label(Yii::t("app", "No-Reply e-mail"), 'app_noreplyEmail', ['class' => 'control-label']) ?>
                            <?= Html::textInput('app_noreplyEmail', Yii::$app->formsettings->get('app.noreplyEmail'), ['class' => 'form-control']) ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group" style="text-align: right; margin-top: 20px">
                            <?= Html::submitButton(Html::tag('i', '', [
                                    'class' => 'glyphicon glyphicon-ok',
                                ]) . ' ' . Yii::t('form', 'Save'), ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php
ActiveForm::end();

$url = \yii\helpers\Url::to('/settings/logo-delete');

$script = <<< JS

$( document ).ready(function(){
    // Handlers
    $('body').on('click', '.file-caption-remove', function(){
        console.log('clicked');
        $.ajax({
          url: "{$url}"
        }).done(function() {
          location.reload();
        });
    });
});

JS;

$this->registerJs($script, $this::POS_END);
?>