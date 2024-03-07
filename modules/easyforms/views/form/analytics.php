<?php

use yii\web\View;
use yii\helpers\Html;
use yii\helpers\Url;
use app\modules\easyforms\bundles\VisualizationBundle;

/* @var $this yii\web\View */
/* @var $formModel app\models\Form */
/* @var $formDataModel app\models\FormData */

VisualizationBundle::register($this);

$this->title = $formModel->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('form', 'Forms'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $formModel->name, 'url' => ['view', 'id' => $formModel->id]];
$this->params['breadcrumbs'][] = Yii::t('form', 'Performance Analytics');

// PHP options required by form.analytics.js
$options = array(
    "endPoint" => Url::to(['ajax/analytics', 'id' => $formModel->id]),
    "i18n" => [
        "users" => Yii::t('form', 'Users'),
        "beganFilling" => Yii::t('form', 'Began Filling'),
        "conversions" => Yii::t('form', 'Conversions'),
        "medianPerDay" => Yii::t('form', 'Median per Day'),
        "minutes" => Yii::t('form', 'minutes'),
        "months" => [
            Yii::t('form', 'Jan'),
            Yii::t('form', 'Feb'),
            Yii::t('form', 'Mar'),
            Yii::t('form', 'Apr'),
            Yii::t('form', 'May'),
            Yii::t('form', 'Jun'),
            Yii::t('form', 'Jul'),
            Yii::t('form', 'Aug'),
            Yii::t('form', 'Sep'),
            Yii::t('form', 'Oct'),
            Yii::t('form', 'Nov'),
            Yii::t('form', 'Dec')
        ],
        "days" => [
            Yii::t('form', 'Sun'),
            Yii::t('form', 'Mon'),
            Yii::t('form', 'Tue'),
            Yii::t('form', 'Wed'),
            Yii::t('form', 'Thu'),
            Yii::t('form', 'Fri'),
            Yii::t('form', 'Sat'),
        ],
    ],
);

// Pass php options to javascript before VisualizationBundle
$this->registerJs("var options = " . json_encode($options) . ";", View::POS_BEGIN, 'analytics-options');

// Load form.analytics.js after VisualizationBundle
$this->registerJsFile('@web/static_files/js/form.analytics.js', ['depends' => VisualizationBundle::className()]);

?>
<div class="analytics-page box box-big box-light">

    <div class="pull-right">
        <small>
            <?= Html::a(
                Yii::t('form', 'Submissions Analytics') . ' <span class="glyphicon glyphicon-arrow-right"> </span> ',
                ['stats', 'id' => $formModel->id],
                ['title' => Yii::t('form', 'Go to Submissions Analytics'), 'class' => 'text-muted hidden-xs']
            ) ?></small>
    </div>

    <div class="box-header">
        <h3 class="box-title"><?= Html::encode($this->title) ?>
            <span class="box-subtitle"><?= Yii::t('form', 'Performance Analytics') ?></span>
        </h3>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="data-count" style="float: left;">
                <span>
                    <?= Yii::t(
                        "app",
                        "You're visualizing the form performance of {filterCount} days from a total of {totalCount} days.",
                        [
                            "filterCount" => "<span class='filter-count'></span>",
                            "totalCount" => "<span class='total-count'></span>"]
                    ); ?>
                    <a href="javascript:dc.filterAll(); dc.renderAll();"><?= Yii::t('form', 'Reset All') ?></a>.</span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div id="conversion-rates">
                <ul>
                    <li>
                        <div>
                            <span><?= Yii::t('form', 'Users') ?></span>
                            <h2 id="users-number"></h2>
                            <span id="fills-rate" class="percentage"></span>
                        </div>
                    </li>
                    <li>
                        <div>
                            <span><?= Yii::t('form', 'Began Filling') ?></span>
                            <h2 id="fills-number"></h2>
                            <span id="completition-rate" class="percentage"></span>
                        </div>
                    </li>
                    <li>
                        <div>
                            <span><?= Yii::t('form', 'Conversions') ?></span>
                            <h2 id="conversions-number"></h2>
                        </div>
                    </li>
                    <li>
                        <div>
                            <span><?= Yii::t('form', 'Conversion Rate') ?></span>
                            <h2><span id="conversion-rate"></span>%</h2>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class='row'>
        <div class="col-md-12">
            <h4><?= Yii::t('form', 'Timeline') ?></h4>
            <div id="overview">
                <div id="overview-chart"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <h4><?= Yii::t('form', 'By year') ?></h4>
            <div id="year">
                <div id="year-chart"></div>
            </div>
        </div>
        <div class="col-md-5">
            <h4><?= Yii::t('form', 'By month') ?></h4>
            <div id="month">
                <div id="month-chart"></div>
            </div>
        </div>
        <div class="col-md-3">
            <h4><?= Yii::t('form', 'By day') ?></h4>
            <div id="week">
                <div id="day-of-week-chart"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <h4><?= Yii::t('form', 'Conversion Time') ?></h4>
            <div id="conversion-time">
                <div id="conversion-time-chart"></div>
            </div>
        </div>
        <div class="col-md-9">
            <h4><?= Yii::t('form', 'Conversion Time Average') ?></h4>
            <div id="conversion-time-line">
                <div id="conversion-time-line-chart"></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3">
            <h4><?= Yii::t('form', 'Conversion vs Abandonment') ?></h4>
            <div id="abandonment">
                <div id="abandonment-chart"></div>
            </div>
        </div>
        <div class="col-md-9">
            <h4><?= Yii::t('form', 'Abandonment Rate') ?></h4>
            <div id="abandonment-time">
                <div id="abandonment-time-chart"></div>
            </div>
        </div>
    </div>

</div>