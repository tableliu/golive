<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\easyforms\bundles\SubmissionsReportBundle;

/* @var $this yii\web\View */
/* @var $formModel app\models\Form */
/* @var $formDataModel app\models\FormData */
/* @var $charts array */

SubmissionsReportBundle::register($this);

$this->title = $formModel->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('form', 'Forms'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $formModel->name, 'url' => ['view', 'id' => $formModel->id]];
$this->params['breadcrumbs'][] = ['label' => Yii::t("app", "Submissions"),
    'url' => ['submissions', 'id' => $formModel->id]];
$this->params['breadcrumbs'][] = Yii::t('form', 'Report');

// Form Labels
$formLabels = $formDataModel->getLabels();

// Home URL$
$homeUrl = Url::home(true);

// Pass php options to javascript before SubmissionsReportBundle
$options = array(
    "endPoint" => Url::to(['ajax/report', 'id' => $formModel->id]),
    "charts" => $charts,
    "_csrf" => Yii::$app->request->getCsrfToken(),
    "i18n" => [
        'success' => Yii::t('form', 'Success!'),
        'error' => Yii::t('form', 'Error!'),
        'errorMessage' => Yii::t('form', 'Please write the Chart Title.'),
        'updatedMessage' => Yii::t('form', 'The report has been successfully updated.'),
        'errorOnUpdate' => Yii::t('form', 'The report can\'t be saved. Please retry later.'),
    ]
);

$this->registerJs("var options = " . json_encode($options) . ";", $this::POS_BEGIN, 'report-options');

// Add print css
$this->registerCssFile('@web/static_files/css/print.report.css', [
    'depends' => [SubmissionsReportBundle::className()],
    'media' => 'print',
], 'css-print-report');

?>
<div class="report-page box box-big box-light">

    <div class="buttons">
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-default btn-sm" data-toggle="modal" data-target="#formModal">
            <?= Yii::t("app", "Add chart") ?>
        </button>
        <button type="button" class="btn btn-default btn-sm btn-for-toggle" id="enable">
            <?= Yii::t("app", "Edit") ?>
        </button>
        <button type="button" class="btn btn-default btn-sm btn-for-toggle" id="disable">
            <?= Yii::t("app", "Stop") ?>
        </button>
        <button type="button" class="btn btn-default btn-sm" id="reset">
            <?= Yii::t("app", "Reset") ?>
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="saveReport">
            <?= Yii::t("app", "Save report") ?>
        </button>
    </div>

    <div class="box-header">
        <h3 class="box-title"><?= Html::encode($this->title) ?>
            <span class="box-subtitle"><?= Yii::t('form', 'Submissions Report') ?></span>
        </h3>
    </div>

    <div id="messages"></div>

    <div class="data-count">
        <p><?= Yii::t(
                "app",
                "You're visualizing the report of {filterCount} submissions from a total of {totalCount} submissions.",
                [
                    "filterCount" => "<span class='filter-count'></span>",
                    "totalCount" => "<span class='total-count'></span>"
                ]
            ); ?> <a href="javascript:dc.filterAll(); dc.renderAll();"><?= Yii::t('form', 'Reset All') ?></a>.</p>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="formModal" tabindex="-1" role="dialog"
         aria-labelledby="<?= Yii::t("app", "Save Chart") ?>">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="formChartLabel"><?= Yii::t("app", "Chart") ?></h4>
                </div>
                <div class="modal-body">
                    <div id="modal-messages"></div>
                    <form id="formChart" onsubmit="return false;">
                        <div class="form-group required-control">
                            <label for="chartTitle" class="control-label"><?= Yii::t("app", "Title") ?></label>
                            <input type="text" class="form-control" id="chartTitle"
                                   placeholder="<?= Yii::t("app", "Title") ?>">
                        </div>
                        <div class="form-group">
                            <label for="chartType"><?= Yii::t('form', 'Type') ?></label>
                            <select class="form-control" id="chartType">
                                <option value="pie"><?= Yii::t("app", "Pie Chart") ?></option>
                                <option value="donut"><?= Yii::t("app", "Donut Chart") ?></option>
                                <option value="row"><?= Yii::t("app", "Row Chart") ?></option>
                                <option value="bar"><?= Yii::t("app", "Bar Chart") ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="field"><?= Yii::t("app", "Data Field") ?></label>
                            <select class="form-control" id="field">
                                <?php foreach ($formLabels as $key => $label) { ?>
                                    <option value="<?= $key ?>"><?= $label ?></option>
                                <?php }; ?>
                            </select>
                            <p class="help-block">
                                <?= Yii::t(
                                    "app",
                                    "Only one chart by data field can be created. If the chart exists, it will be updated."
                                ) ?></p>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <?= Yii::t("app", "Close") ?></button>
                    <button type="button" class="btn btn-primary" id="saveChart" data-dismiss="modal">
                        <?= Yii::t("app", "Save") ?></button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid-stack"></div>

</div>

<!-- widget -->
<script type="text/html" id="widget">
    <div data-title="{{- title }}" data-type="{{= type }}" data-name="{{= name }}" data-label="{{= label }}">
        <div class="grid-stack-item-content">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="summary">{{- title }}</div>
                    <div class="pull-right">
                        <a href="#" class="editChart" data-toggle="modal" data-target="#formModal"
                           data-title="{{- title }}" data-type="{{= type }}" data-name="{{= name }}"
                           data-label="{{= label }}" onclick="return false;">
                            <i class="glyphicon glyphicon-pencil"></i>
                        </a>
                        <a href="#" class="deleteChart" data-name="{{= name }}"
                           onclick="deleteChart(this); return false;">
                            <i class="glyphicon glyphicon-remove"></i>
                        </a>
                    </div>
                </div>
                <div class="panel-body">
                    <div class="chart" id="{{= name }}"></div>
                </div>
            </div>
        </div>
    </div>
</script>