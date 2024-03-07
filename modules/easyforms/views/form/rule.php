<?php

use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\easyforms\bundles\RulesBuilderBundle;

RulesBuilderBundle::register($this);

/* @var $this yii\web\View */
/* @var $formModel app\models\Form */
/* @var $formDataModel app\models\FormData */

$this->title = $formModel->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('form', 'Forms'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $formModel->name, 'url' => ['view', 'id' => $formModel->id]];
$this->params['breadcrumbs'][] = Yii::t('form', 'Rule Builder');

// PHP options required by report.js
$options = array(
    "formID" => $formModel->id,
    "endPoint" => Url::to(['rules/index']),
    'createEndPoint' => Url::to(['rules/create']),
    'updateEndPoint' => Url::to(['rules/update']),
    'deleteEndPoint' => Url::to(['rules/delete']),
    'positionEndPoint' => Url::to(['rules/position']),
    'hasPrettyUrls' => Yii::$app->urlManager->enablePrettyUrl,
    "_csrf" => Yii::$app->request->getCsrfToken(),
    "variables" => $formDataModel->getRuleVariables(),
    "fields" => $formDataModel->getRuleFields(),
    "steps" => $formDataModel->getRuleSteps(),
    "i18n" => [
        // Conditions and actions
        'all' => Yii::t('form', 'All'),
        'any' => Yii::t('form', 'Any'),
        'none' => Yii::t('form', 'None'),
        'addAction' => Yii::t('form', 'Add action'),
        'addCondition' => Yii::t('form', 'Add condition'),
        'addGroup' => Yii::t('form', 'Add group'),
        'deleteText' => Yii::t('form', 'Delete'),
        'followingActions' => ' ' . Yii::t('form', 'Executes the following actions:'),
        'followingConditions' => ' ' . Yii::t('form', 'of the following conditions:'),
        // Operators
        'contains' => Yii::t('form', 'contains'),
        'is' => Yii::t('form', 'is'),
        'isNot' => Yii::t('form', 'is not'),
        'isChecked' => Yii::t('form', 'is checked'),
        'isNotChecked' => Yii::t('form', 'is not checked'),
        'isPresent' => Yii::t('form', 'is present'),
        'isBlank' => Yii::t('form', 'is blank'),
        'isAfter' => Yii::t('form', 'is after'),
        'isBefore' => Yii::t('form', 'is before'),
        'isEqualTo' => Yii::t('form', 'is equal to'),
        'isGreaterThan' => Yii::t('form', 'is greater than'),
        'isGreaterThanOrEqual' => Yii::t('form', 'is greater than or equal'),
        'isLessThan' => Yii::t('form', 'is less than'),
        'doesNotContains' => Yii::t('form', 'does not contains'),
        'hasAValue' => Yii::t('form', 'has a value'),
        'hasNoValue' => Yii::t('form', 'has no value'),
        'hasOptionSelected' => Yii::t('form', 'has option selected'),
        'hasNoOptionSelected' => Yii::t('form', 'has no option selected'),
        'hasFileSelected' => Yii::t('form', 'has file selected'),
        'hasNoFileSelected' => Yii::t('form', 'has no file selected'),
        'hasBeenClicked' => Yii::t('form', 'has been clicked'),
        'hasBeenSubmitted' => Yii::t('form', 'has been submitted'),
        'startsWith' => Yii::t('form', 'starts with'),
        'endsWith' => Yii::t('form', 'ends with'),
        // App
        'show' => Yii::t('form', 'Show'),
        'hide' => Yii::t('form', 'Hide'),
        'enable' => Yii::t('form', 'Enable'),
        'disable' => Yii::t('form', 'Disable'),
        'math' => Yii::t('form', 'Math'),
        'perform' => Yii::t('form', 'Perform'),
        'addition' => Yii::t('form', 'Addition'),
        'subtraction' => Yii::t('form', 'Subtraction'),
        'multiplication' => Yii::t('form', 'Multiplication'),
        'division' => Yii::t('form', 'Division'),
        'remainder' => Yii::t('form', 'Remainder'),
        'field' => Yii::t('form', 'Field'),
        'element' => Yii::t('form', 'Element'),
        'of' => Yii::t('form', 'Of'),
        'as' => Yii::t('form', 'As'),
        'toStep' => Yii::t('form', 'To Step'),
        'copy' => Yii::t('form', 'Copy'),
        'from' => Yii::t('form', 'From'),
        'to' => Yii::t('form', 'To'),
        'skip' => Yii::t('form', 'Skip'),
        'andSetResultTo' => Yii::t('form', 'And set result to'),
        'formatNumber' => Yii::t('form', 'Format Number'),
        'formatText' => Yii::t('form', 'Format Text'),
        // Others
        'orderUpdated' => Yii::t('form', 'Rule order updated!'),
        'areYouSureDeleteItem' => Yii::t('form', 'Are you sure you want to delete this rule? All data related to this item will be deleted. This action cannot be undone.'),
    ]
);

// Pass php options to javascript before RulesBuilderBundle
$this->registerJs("var options = ".json_encode($options).";", View::POS_BEGIN, 'rule-options');

?>
<div class="rules-page">

    <div class="page-header">
        <h1><?= Html::encode($this->title) ?> <small><?= Yii::t("app", "Rule Builder") ?></small></h1>
    </div>

    <?php if (count($formDataModel->getFields()) == 0) { ?>
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger alert-dismissable">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <p> <span class="glyphicon glyphicon-remove-sign"> </span>
                        <?= Yii::t("app", "To create your first rule, you must add fields to your form.") ?>
                        <?= Html::a(Yii::t('form', 'Go to Form Builder'), ['update', 'id'=>$formModel->id], [
                            'class' => 'alert-link'
                        ]) ?>.</p>
                </div>
            </div>
        </div>
    <?php } else { ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><span class="glyphicon glyphicon-flowchart"> </span>
                    <?= Yii::t("app", "Conditional Rules") ?></h3>
            </div>
            <div class="panel-body">
                <div id="main">
                </div>
            </div>
        </div>

    <?php } ?>

    <script type="text/template" id="actions-template">
        <div class="clearfix" style="margin-bottom: 10px">
            <div class="pull-right">
                <button id="add-rule" class="btn btn-primary">
                    <span class="glyphicon glyphicon-plus"> </span> <?= Yii::t('form', 'Add rule') ?> </button>
            </div>
        </div>
    </script>

    <script type="text/template" id="rules-template">
    </script>

    <script type="text/template" id="rule-template">
        <div>
            <div class="pull-right">
                <span class="label label-warning" style="display: none"><?= Yii::t('form', 'Unsaved Changes') ?></span>
                <a class="btn btn-success save-rule" title="Save rule"><i class="glyphicon glyphicon-ok"></i></a>
                <a class="btn btn-primary duplicate-rule" title="Duplicate rule"><i class="glyphicon glyphicon-duplicate"></i></a>
                <a class="btn btn-danger delete-rule" title="Delete rule"><i class="glyphicon glyphicon-bin"></i></a>
            </div>
        </div>
        <form id="{{= cid }}conditions" class="rule-builder-conditions"></form>
        <form id="{{= cid }}actions" class="rule-builder-actions"></form>
        <div class="clearfix" >
            <div class="settings">
                <div class="checkbox checkbox-warning">
                    <input id="{{= cid }}opposite" class="styled" type="checkbox"{{ if (rule.opposite) { }} checked{{ } }}>
                    <label for="{{= cid }}opposite">
                        <?= Yii::t('form', 'Opposite actions') ?>
                    </label>
                </div>
                <div class="checkbox checkbox-warning">
                    <input id="{{= cid }}status" class="styled" type="checkbox"{{ if (rule.status) { }} checked{{ } }}>
                    <label for="{{= cid }}status">
                        <?= Yii::t('form', 'Enabled') ?>
                    </label>
                </div>
            </div>
        </div>
    </script>
</div>
