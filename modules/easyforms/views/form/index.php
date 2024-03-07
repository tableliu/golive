<?php

use app\modules\easyforms\components\widgets\ActionBar;
use app\modules\easyforms\components\widgets\GridView;
use app\modules\easyforms\components\widgets\PageSizeDropDownList;
use app\modules\easyforms\helpers\Html;
use app\modules\easyforms\helpers\Language;
use Carbon\Carbon;
use kartik\switchinput\SwitchInput;
use yii\bootstrap\Dropdown;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\FormSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $this yii\web\View */
/* @var $templates array */
$this->title = Yii::t("form", "Form");
$this->params['breadcrumbs'][] = $this->title;
Html::a('Create Dictionary', ['create'], ['class' => 'btn btn-success']);


// Prepare dropdown with templates array
$templateItems = array();
if (count($templates) > 0) {
    // Set data for dropdown widget
    foreach ($templates as $template) {
        $item = [
            'label' => $template['name'],
            'url' => Url::to(['create', 'template' => $template['slug']]),
        ];
        array_push($templateItems, $item);
    }
    $itemDivider = [
        'label' => '<li role="presentation" class="divider"></li>',
        'encode' => false,
    ];
    array_push($templateItems, $itemDivider);
}

// Add link to templates
$itemMoreTemplates = [
    'label' => Yii::t('form', 'More Templates'),
    'url' => Url::to(['/templates']),
];
array_push($templateItems, $itemMoreTemplates);

/** @var kartik\datecontrol\Module $dateControlModule */
$dateControlModule = \Yii::$app->getModule('datecontrol');

Carbon::setLocale(substr(Yii::$app->language, 0, 2)); // eg. en-US to en

$options = array(
    'currentPage' => Url::toRoute(['index']), // Used by filters
);

// Pass php options to javascript
$this->registerJs("var options = " . json_encode($options) . ";", View::POS_BEGIN, 'form-options');
?>
    <p>
        <?= Html::a(Yii::t('form', 'Create form'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
<?php

$gridColumns = [
    [
        'class' => '\kartik\grid\CheckboxColumn',
        'headerOptions' => ['class' => 'kartik-sheet-style'],
        'rowSelectedClass' => 'warning',
    ],
    [
        'class' => 'kartik\grid\BooleanColumn',
        'attribute' => 'status',
        'trueIcon' => '<span class="glyphicon glyphicon-ok text-success"></span>',
        'falseIcon' => '<span class="glyphicon glyphicon-remove text-danger"></span>',
        'vAlign' => 'middle',
    ],
    [
        'attribute' => 'name',
        'format' => 'raw',
        'value' => function ($model) {
            return Html::a(Html::encode($model->name), ['form/view', 'id' => $model->id]);
        },
    ],
//    [
//        'attribute' => 'language',
//        'value' => 'languageLabel',
//        'filter' => Html::activeDropDownList(
//            $searchModel,
//            'language',
//            Language::supportedLanguages(),
//            ['class' => 'form-control', 'prompt' => '']
//        ),
//    ],
    [
        'attribute' => 'created_by',
        'value' => function ($model) {
            return isset($model->author->fullName->full_name) ? Html::encode($model->author->fullName->full_name) : null;
        },
        'label' => Yii::t("form", "Created by")
    ],
    [
        'attribute' => 'created_at',
        'value' => function ($model) {
          return date("Y-m-d H:i",$model->created_at);
           // Html::encode($model->created_at);
        },
        'label' => Yii::t("form", "Created at")
    ],
    [
        'attribute' => 'updated_at',
        'value' => function ($model) {
            return date("Y-m-d H:i",$model->updated_at);
        },
        'label' => Yii::t("form", "Updated at")
    ],
//    [
//        'attribute' => 'updated_at',
//        'value' => function ($model) {
//            return Carbon::createFromTimestampUTC($model->updated_at)->diffForHumans();
//        },
//        'label' => Yii::t('form', 'Updated'),
//        'filterType' => GridView::FILTER_DATE_RANGE,
//        'filterWidgetOptions' => [
//            'presetDropdown' => false,
//            'convertFormat' => true,
//            'containerTemplate' => '
//        <div class="form-control kv-drp-dropdown">
//            <i class="glyphicon glyphicon-calendar"></i>&nbsp;
//            <span class="range-value">{value}</span>
//            <span><b class="caret"></b></span>
//        </div>
//        {input}
//',
//            'pluginOptions' => [
//                'showDropdowns' => true,
//                'locale' => [
//                    //'format' => $dateControlModule->getDisplayFormat(DateControlModule::FORMAT_DATE),
//                    'separator' => ' - ',
//                ],
//                'opens' => 'left'
//            ]
//        ],
//    ],
    ['class' => '\kartik\grid\ActionColumn',
        'controller' => 'form',
        // Visible for all users
        'visible' => true,
        'dropdown' => true,
        'dropdownButton' => ['class' => 'btn btn-primary'],
        'dropdownOptions' => ['class' => 'pull-right'],
        'buttons' => [
            //update button
            'update' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-pencil"> </span> ' . Yii::t('form', 'Update'),
                        $url,
                        ['title' => Yii::t('form', 'Update')]
                    ) . '</li>';
            },
            //settings button
            'settings' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-cogwheel"> </span> ' . Yii::t('form', 'Settings'),
                        $url,
                        ['title' => Yii::t('form', 'Settings')]
                    ) . '</li>';
            },
            //rule button
            'rules' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-flowchart"> </span> ' . Yii::t('form', 'Conditional Rules'),
                        $url,
                        ['title' => Yii::t('form', 'Conditional Rules')]
                    ) . '</li>';
            },
            //preview form button
            'view' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-eye-open"> </span> ' . Yii::t('form', 'View Record'),
                        $url,
                        ['title' => Yii::t('form', 'View Record')]
                    ) . '</li>';
            },
            //copy button
            'copy' => function ($url) {
                $options = array_merge([
                    'title' => Yii::t('form', 'Copy'),
                    'aria-label' => Yii::t('form', 'Copy'),
                    'data-method' => 'post',
                    'data-pjax' => '0',
                ], []);
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-duplicate"> </span> ' .
                        Yii::t('form', 'Copy'),
                        $url,
                        $options
                    ) . '</li>';
            },
            //share form button
            'share' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-share"> </span> ' . Yii::t('form', 'Publish & Share'),
                        $url,
                        ['title' => Yii::t('form', 'Publish & Share')]
                    ) . '</li>';
            },
            //form submissions button
            'submissions' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-send"> </span> ' . Yii::t('form', 'Submissions'),
                        $url,
                        ['title' => Yii::t('form', 'Submissions')]
                    ) . '</li>';
            },
            //form report button
            'report' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-pie-chart"> </span> ' . Yii::t('form', 'Submissions Report'),
                        $url,
                        ['title' => Yii::t('form', 'Submissions Report')]
                    ) . '</li>';
            },
            //form analytics button
            'analytics' => function ($url) {
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-charts"> </span> ' . Yii::t('form', 'Form Analytics'),
                        $url,
                        ['title' => Yii::t('form', 'Form & Submissions Analytics')]
                    ) . '</li>';
            },
            //reset stats button
            'reset_stats' => function ($url) {
                $options = array_merge([
                    'title' => Yii::t('form', 'Reset Stats'),
                    'aria-label' => Yii::t('form', 'Reset Stats'),
                    'data-confirm' => Yii::t('form', 'Are you sure you want to delete these stats? All stats related to this item will be deleted. This action cannot be undone.'),
                    'data-method' => 'post',
                    'data-pjax' => '0',
                ], []);
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-refresh"> </span> ' .
                        Yii::t('form', 'Reset Stats'),
                        $url,
                        $options
                    ) . '</li>';
            },
            //delete button
            'delete' => function ($url) {
                $options = array_merge([
                    'title' => Yii::t('form', 'Delete'),
                    'aria-label' => Yii::t('form', 'Delete'),
                    'data-confirm' => Yii::t('form', 'Are you sure you want to delete this form? All stats, submissions, conditional rules and reports data related to this item will be deleted. This action cannot be undone.'),
                    'data-method' => 'post',
                    'data-pjax' => '0',
                ], []);
                return '<li>' . Html::a(
                        '<span class="glyphicon glyphicon-bin"> </span> ' .
                        Yii::t('form', 'Delete'),
                        $url,
                        $options
                    ) . '</li>';
            },
        ],
        'urlCreator' => function ($action, $model) {
            if ($action === 'update') {
                $url = Url::to(['form/update', 'id' => $model->id]);
                return $url;
            } elseif ($action === "settings") {
                $url = Url::to(['form/settings', 'id' => $model->id]);
                return $url;
            } elseif ($action === "rules") {
                $url = Url::to(['form/rules', 'id' => $model->id]);
                return $url;
            } elseif ($action === "view") {
                $url = Url::to(['form/view', 'id' => $model->id]);
                return $url;
            } elseif ($action === "copy") {
                $url = Url::to(['form/copy', 'id' => $model->id]);
                return $url;
            } elseif ($action === "share") {
                $url = Url::to(['form/share', 'id' => $model->id]);
                return $url;
            } elseif ($action === "submissions") {
                $url = Url::to(['form/submissions', 'id' => $model->id]);
                return $url;
            } elseif ($action === "report") {
                $url = Url::to(['form/report', 'id' => $model->id]);
                return $url;
            } elseif ($action === "analytics") {
                $url = Url::to(['form/analytics', 'id' => $model->id]);
                return $url;
            } elseif ($action === "reset_stats") {
                $url = Url::to(['form/reset-stats', 'id' => $model->id]);
                return $url;
            } elseif ($action === "delete") {
                $url = Url::to(['form/delete', 'id' => $model->id]);
                return $url;
            }
            return '';
        },
//            'template' => Yii::$app->user->can('edit_own_content') ?
//                '{update} {settings} {rules} {view} {copy} {share} {submissions} {report} {analytics} {reset_stats} {delete}' :
//                '{view} {share} {submissions} {report} {analytics}',
        'template' =>
            '{update} {settings} {rules} {view} {copy} {share} {submissions} {report}{delete}'

    ],
];

?>

    <div class="form-index">
        <div class="row">
            <div class="col-md-12">
                <?= GridView::widget([
                    'id' => 'form-grid',
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => $gridColumns,
                    'resizableColumns' => false,
                    'pjax' => false,
                    'export' => false,
                    'responsive' => true,
                    'bordered' => false,
                    'striped' => true,
                    'panelTemplate' => Yii::$app->user->can('edit_own_content') ?
                        Html::tag('div', '{panelHeading}{panelBefore}{items}{panelFooter}', ['class' => 'panel {type}']) :
                        Html::tag('div', '{panelHeading}{items}{panelFooter}', ['class' => 'panel {type}']),
                    'panel' => [
                        'type' => GridView::TYPE_INFO,
                        'heading' => Yii::t('form', '') . ' <small class="panel-subtitle hidden-xs">' .
                            Yii::t('form', '') . '</small>',
                        // Not visible for basic user
                        'before' =>
                            ActionBar::widget([
                                'grid' => 'form-grid',
                                'templates' => [
                                    '{create}' => ['class' => 'col-xs-6 col-md-8'],
                                    '{filters}' => ['class' => 'col-xs-2 col-md-2 no-padding'],
                                    '{bulk-actions}' => ['class' => 'col-xs-4 col-md-2'],
                                ],
                                'elements' => [
                                    'create' =>
                                        '<div class="btn-group">' .
                                        Html::a(
                                            '<span class="glyphicon glyphicon-plus hidden-xs"></span> ' .
                                            Yii::t('form', 'Create Form'),
                                            ['create'],
                                            ['class' => 'btn btn-primary']
                                        ) .
                                        '<button type="button" class="btn btn-primary dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="caret"></span>
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>' .
                                        Dropdown::widget(['items' => $templateItems]) .
                                        '</div> ' .
                                        Html::a(Yii::t('form', 'Do you want to customize your form?'), ['/theme'], [
                                            'data-toggle' => 'tooltip',
                                            'data-placement' => 'top',
                                            'title' => Yii::t('form', 'No problem at all. With a theme, you can easily add custom CSS styles to your form, to customize colors, field sizes, backgrounds, fonts, and more.'),
                                            'class' => 'text hidden-xs hidden-sm']),
                                    'filters' => SwitchInput::widget(
                                        [
                                            'name' => 'filters',
                                            'type' => SwitchInput::CHECKBOX,
                                            'pluginOptions' => [
                                                'size' => 'mini',
                                                'animate' => false,
                                                'labelText' => Yii::t('form', 'Filter'),
                                            ],
                                            'pluginEvents' => [
                                                "switchChange.bootstrapSwitch" => "function(event, state) {
                                                        if (state) {
                                                            $('.filters').fadeIn()
                                                            localStorage.setItem('gridView.filters', 1);
                                                        } else {
                                                            $('.filters').fadeOut()
                                                            localStorage.setItem('gridView.filters', 0);
                                                            window.location = options.currentPage;
                                                        }
                                                    }",
                                            ],
                                            'containerOptions' => ['style' => 'margin-top: 6px; text-align: right'],
                                        ]
                                    ),
                                ],
                                'bulkActionsItems' => [
                                    Yii::t('form', 'Update Status') => [
                                        'status-active' => Yii::t('form', 'Active'),
                                        'status-inactive' => Yii::t('form', 'Inactive'),
                                    ],
                                    Yii::t('form', 'General') => ['general-delete' => Yii::t('form', 'Delete')],
                                ],
                                'bulkActionsOptions' => [
                                    'options' => [
                                        'status-active' => [
                                            'url' => Url::toRoute(['update-status', 'status' => 1]),
                                        ],
                                        'status-inactive' => [
                                            'url' => Url::toRoute(['update-status', 'status' => 0]),
                                        ],
                                        'general-delete' => [
                                            'url' => Url::toRoute('delete-multiple'),
                                            'data-confirm' => Yii::t('form', 'Are you sure you want to delete these form? All stats, submissions, conditional rules and reports data related to each item will be deleted. This action cannot be undone.'),
                                        ],
                                    ],
                                    'class' => 'form-control',
                                ],

                                'class' => 'form-control',
                            ]),
                    ],
                    'replaceTags' => [
                        '{pageSize}' => function ($widget) {
                            $html = '';
                            if ($widget->panelFooterTemplate !== false) {
                                $selectedSize = 10;
                                return PageSizeDropDownList::widget(['selectedSize' => $selectedSize]);
                            }
                            return $html;
                        },
                    ],
                    'panelFooterTemplate' => '
                    <div class="kv-panel-pager">
                        {pageSize}
                        {pager}
                    </div>
                ',
                    'toolbar' => false
                ]); ?>
            </div>
        </div>
    </div>
<?php
$js = <<< 'SCRIPT'

$(function () {
    // Tooltips
    $("[data-toggle='tooltip']").tooltip();
    // Filters
    var state = localStorage.getItem('gridView.filters');
    if (typeof state !== undefined && state == 1) {
        $('input[name="filters"]').bootstrapSwitch('state', true);
    } else {
        $('input[name="filters"]').bootstrapSwitch('state', false);
    }
});

SCRIPT;
// Register tooltip/popover initialization javascript
$this->registerJs($js);