<?php

use  app\modules\easyforms\bundles\FormBuilderBundle;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\Form */

FormBuilderBundle::register($this);

// PHP options required by main-built.js
$options = array(
    "homeUrl" => Url::home(true),
    "libUrl" => Url::to('@web/static_files/js/form.builder/lib/'),
    "i18nUrl" => Url::to(['ajax/builder-phrases']),
    "componentsUrl" => Url::to(['ajax/builder-components']),
    "initPoint" => Url::to(['ajax/init-form', 'id' => $model->id]),
    "endPoint" => Url::to(['ajax/update-form', 'id' => $model->id]),
    "reCaptchaSiteKey" => Yii::$app->formsettings->get("app.reCaptchaSiteKey"),
    "afterSave" => 'showMessage', // Or 'redirect'
    "redirectTo" => Url::to(['/form']),
    "_csrf" => Yii::$app->request->getCsrfToken(),
);

// Pass php options to javascript
$this->registerJs("var options = " . json_encode($options) . ";", View::POS_BEGIN, 'builder-options');

$this->title = Yii::t('form', 'Update Form');
?>

<div class="form-update">
    <div id="ef-loading" class="ef-loading">
        <div class="ef-loader">
            <span>
                <?= Html::img(Yii::getAlias('@web/static_files/images/loading.gif'), [
                    'height' => '50px',
                    'width' => '50px',
                    'alt' => Yii::t('form', 'Loading'),
                    'title' => Yii::t('form', 'Loading'),
                ]) ?>
            </span>
            <span class="text-muted"><?= Yii::t('form', 'Loading') ?></span>
        </div>
    </div>
    <div id="ef-form-builder" class="row" style="display: none">
        <!-- Widgets -->
        <div id="ef-widgets" class="col-xs-12 col-md-4 sidebar-outer">
            <div class="sidebar">
                <div class="panel">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs nav-justified" id="formtabs" role="tablist">
                        <!-- Tab nav -->
                    </ul>
                    <form id="widgets">
                        <fieldset>
                            <!-- Tab panes -->
                            <div class="tab-content">
                                <!-- Tabs of widgets go here -->
                            </div>
                        </fieldset>
                    </form>
                </div>
                <div id="ef-switcher-side-left" class="ef-switcher ef-switcher-side-left">
                    <div id="ef-switcher-inner" class="ef-switcher-inner">
                        <label id="ef-switcher-preview" class="ef-switcher-preview"
                               title="<?= Yii::t('form', 'Hide Panel') ?>">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <!-- / Widgets -->

        <!-- Building Form. -->
        <div id="ef-main" class="col-xs-12 col-md-5">
            <!-- Alert. -->
            <div class="alert alert-warning alert-dismissable fade" style="display: none">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <strong><?= Yii::t("app", "Tip") ?>:</strong>
                <?= Yii::t(
                    "app",
                    "Just Drag the Fields on center to start building your form. It's fast, easy & fun."
                ) ?>
            </div>
            <!-- / Alert. -->
            <div id="ef-switcher-main-left" class="ef-switcher ef-switcher-main-left">
                <div id="ef-switcher-inner" class="ef-switcher-inner">
                    <label id="ef-switcher-preview" class="ef-switcher-preview"
                           title="<?= Yii::t('form', 'Show Panel') ?>">
                        <span class="glyphicon glyphicon-chevron-right"></span>
                    </label>
                </div>
            </div>
            <div id="canvas">
                <form id="my-form">
                </form>
            </div>
            <div id="ef-switcher-main-right" class="ef-switcher ef-switcher-main-right">
                <div id="ef-switcher-inner" class="ef-switcher-inner">
                    <label id="ef-switcher-preview" class="ef-switcher-preview"
                           title="<?= Yii::t('form', 'Show Panel') ?>">
                        <span class="glyphicon glyphicon-chevron-left"></span>
                    </label>
                </div>
            </div>
            <div id="messages">
                <div data-alerts="alerts"
                     data-titles="{'warning': '<em><?= Yii::t("app", "Warning!") ?></em>'}"
                     data-ids="myid" data-fade="2000"></div>
            </div>
            <div id="actions">
                <input id="formId" type="hidden" value="<?= $model->id ?>">
                <div class="btn-group dropup">
                    <button type="button" class="btn btn-default saveForm"
                            data-endpoint="<?= Url::to(['ajax/update-form', 'id' => $model->id]) ?>"
                            data-aftersave="showMessage" data-redirectto="">
                        <span class="glyphicon glyphicon-ok"></span> <?= Yii::t("app", "Save Form") ?>
                    </button>
                    <?php if (Yii::$app->user->can('edit_own_content')) : ?>
                        <button type="button" class="btn btn-default dropdown-toggle"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a href="#" class="saveForm"
                                   data-endpoint="<?= Url::to(['ajax/create-template']) ?>" data-aftersave="redirect"
                                   data-redirectto="<?= Url::to(['/templates']) ?>">
                                    <?= Yii::t('form', 'Save Form as Template') ?>
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- / Building Form. -->

        <!-- Styles -->
        <div id="ef-styles" class="col-xs-12 col-md-3 sidebar-outer">
            <div class="sidebar-right">
                <div id="ef-switcher-side-right" class="ef-switcher ef-switcher-side-right">
                    <div id="ef-switcher-inner" class="ef-switcher-inner">
                        <label id="ef-switcher-preview" class="ef-switcher-preview"
                               title="<?= Yii::t('form', 'Hide Panel') ?>">
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </label>
                    </div>
                </div>
                <div class="panel panel-default">
                    <div class="panel-heading"><?= Yii::t('form', 'Design') ?></div>
                    <div id="styles" class="panel-body"></div>
                </div>
                <div id="ef-styles-tools" class="ef-styles-tools">
                    <a href="#" id="ef-styles-collapse-all"><?= Yii::t('form', 'Collapse All') ?></a>
                    <span class="ef-styles-separator">|</span>
                    <a href="#" id="ef-styles-expand-all"><?= Yii::t('form', 'Expand All') ?></a>
                </div>
            </div>
        </div>
        <!-- / Styles -->

        <!-- .modal -->
        <div class="modal fade" id="saved">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                title="<?= Yii::t('form', 'I still want to edit this form.') ?>">
                            <span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?= Yii::t("app", "Great! Your form is saved.") ?></h4>
                    </div>
                    <div class="modal-body">
                        <p><?= Yii::t("app", "What do you want to do now?") ?></p>
                        <div class="list-group">
                            <a href="<?= $url = Url::to(['form/update', 'id' => $model->id]); ?>" id="toUpdate"
                               class="list-group-item">
                                <h4 class="list-group-item-heading"><?= Yii::t("app", "It's Ok.") ?></h4>
                                <p class="list-group-item-text">
                                    <?= Yii::t("app", "I still want to edit this form.") ?></p></a>
                            <a href="<?= $url = Url::to(['form/settings', 'id' => $model->id]); ?>" id="toSettings"
                               class="list-group-item">
                                <h4 class="list-group-item-heading">
                                    <?= Yii::t("app", "Let’s go to Form Settings.") ?></h4>
                                <p class="list-group-item-text">
                                    <?= Yii::t("app", "I need to setup my email notifications, confirmation options and more.") ?></p>
                            </a>
                            <a href="<?= $url = Url::to(['form/share', 'id' => $model->id]); ?>" id="toPublish"
                               class="list-group-item">
                                <h4 class="list-group-item-heading">
                                    <?= Yii::t("app", "Share this Form.") ?></h4>
                                <p class="list-group-item-text">
                                    <?= Yii::t("app", "I want to publish and share my form.") ?></p></a>
                            <a href="<?= $url = Url::to(['form/index']); ?>" class="list-group-item">
                                <h4 class="list-group-item-heading">
                                    <?= Yii::t("app", "I finished! Take me back to the Form Manager.") ?></h4>
                                <p class="list-group-item-text">
                                    <?= Yii::t("app", "That's all.") ?></p></a>
                        </div>
                    </div>
                    <div class="modal-footer">
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

    </div>
</div>