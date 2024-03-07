<?php

use yii\helpers\Html;
use kartik\detail\DetailView;
use Carbon\Carbon;

/* @var $this yii\web\View */
/* @var $formModel app\models\Form */

$this->title = isset($formModel->name) ? $formModel->name : $formModel->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('form', 'Forms'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

Carbon::setLocale(substr(Yii::$app->language, 0, 2)); // eg. en-US to en
?>
<div class="form-view box box-big box-light">

    <div class="pull-right hidden-xs" style="margin-top: -5px">
        <?php if (!empty(Yii::$app->user)) : // Advanced user ?>
            <div class="btn-group" role="group">
                <?= Html::a('<span class="glyphicon glyphicon-pencil"></span> ', ['update', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Update'),
                    'class' => 'btn btn-sm btn-info']) ?>
                <?= Html::a('<span class="glyphicon glyphicon-cogwheel"></span> ', ['settings', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Settings'),
                    'class' => 'btn btn-sm btn-info']) ?>
                <?= Html::a('<span class="glyphicon glyphicon-flowchart"></span> ', ['rules', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Conditional Rules'),
                    'class' => 'btn btn-sm btn-info']) ?>
            </div>
        <?php endif; ?>
        <?= Html::a('<span class="glyphicon glyphicon-send"></span> ', ['submissions', 'id' => $formModel->id], [
            'title' => Yii::t('form', 'Submissions'),
            'class' => 'btn btn-sm btn-warning']) ?>
        <div class="btn-group" role="group">
            <?= Html::a('<span class="glyphicon glyphicon-pie-chart"></span> ', ['report', 'id' => $formModel->id], [
                'title' => Yii::t('form', 'Submissions Report'),
                'class' => 'btn btn-sm btn-default']) ?>
        </div>
        <?= Html::a('<span class="glyphicon glyphicon-share"></span> ', ['share', 'id' => $formModel->id], [
            'title' => Yii::t('form', 'Publish & Share'),
            'class' => 'btn btn-sm btn-success']) ?>
        <?php if (!empty(Yii::$app->user)) : // Advanced user ?>
            <div class="btn-group" role="group">
                <?= Html::a('<span class="glyphicon glyphicon-refresh"></span> ', ['reset-stats', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Reset Stats'),
                    'class' => 'btn btn-sm btn-danger',
                    'data' => [
                        'confirm' => Yii::t('form', 'Are you sure you want to delete these stats? All stats related to this item will be deleted. This action cannot be undone.'),
                        'method' => 'post',
                    ],
                ]) ?>
                <?= Html::a('<span class="glyphicon glyphicon-bin"></span> ', ['delete', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Delete'),
                    'class' => 'btn btn-sm btn-danger',
                    'data' => [
                        'confirm' => Yii::t('form', 'Are you sure you want to delete this form? All stats, submissions, conditional rules and reports data related to this item will be deleted. This action cannot be undone.'),
                        'method' => 'post',
                    ],
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="box-header">
        <h3 class="box-title"><?= Yii::t('form', 'Form') ?>
            <span class="box-subtitle"><?= Html::encode($this->title) ?></span>
        </h3>
    </div>

    <div class="buttons visible-xs-block">
        <?php if (!empty(Yii::$app->user) && Yii::$app->user->can("edit_own_content")) : // Advanced user ?>
            <div class="btn-group" role="group">
                <?= Html::a('<span class="glyphicon glyphicon-pencil"></span> ' .
                    Yii::t('form', 'Update'), ['update', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Update'),
                    'class' => 'btn btn-sm btn-info']) ?>
                <?= Html::a('<span class="glyphicon glyphicon-cogwheel"></span> ' .
                    Yii::t('form', 'Settings'), ['settings', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Settings'),
                    'class' => 'btn btn-sm btn-info']) ?>
                <?= Html::a('<span class="glyphicon glyphicon-flowchart"></span> ' .
                    Yii::t('form', 'Rules'), ['rules', 'id' => $formModel->id], [
                    'title' => Yii::t('form', 'Conditional Rules'),
                    'class' => 'btn btn-sm btn-info']) ?>
            </div>
        <?php endif; ?>

        <?= Html::a('<span class="glyphicon glyphicon-send"></span> ' .
            Yii::t('form', 'Submissions'), ['submissions', 'id' => $formModel->id], [
            'title' => Yii::t('form', 'Submissions'),
            'class' => 'btn btn-sm btn-warning']) ?>
        <div class="btn-group" role="group">
            <?= Html::a('<span class="glyphicon glyphicon-pie-chart"></span> ' .
                Yii::t('form', 'Report'), ['report', 'id' => $formModel->id], [
                'title' => Yii::t('form', 'Submissions Report'),
                'class' => 'btn btn-sm btn-default']) ?>
            <?= Html::a('<span class="glyphicon glyphicon-stats"></span> ' .
                Yii::t('form', 'Performance'), ['analytics', 'id' => $formModel->id], [
                'title' => Yii::t('form', 'Performance Analytics'),
                'class' => 'btn btn-sm btn-default']) ?>
            <?= Html::a('<span class="glyphicon glyphicon-charts"></span> ' .
                Yii::t('form', 'Analytics'), ['stats', 'id' => $formModel->id], [
                'title' => Yii::t('form', 'Submissions Analytics'),
                'class' => 'btn btn-sm btn-default']) ?>
        </div>
        <?= Html::a('<span class="glyphicon glyphicon-share"></span> ' .
            Yii::t('form', 'Publish & Share'), ['share', 'id' => $formModel->id], [
            'title' => Yii::t('form', 'Publish & Share'),
            'class' => 'btn btn-sm btn-success']) ?>

        <?php if (!empty(Yii::$app->user) && Yii::$app->user->can("edit_own_content")) : // Advanced user ?>
            <div class="btn-group" role="group">
                <?= Html::a(
                    '<span class="glyphicon glyphicon-refresh"></span> ' . Yii::t('form', 'Reset Stats'),
                    ['reset-stats', 'id' => $formModel->id],
                    [
                        'title' => Yii::t('form', 'Reset Stats'),
                        'class' => 'btn btn-sm btn-danger',
                        'data' => [
                            'confirm' => Yii::t('form', 'Are you sure you want to delete these stats? All stats related to this item will be deleted. This action cannot be undone.'),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
                <?= Html::a(
                    '<span class="glyphicon glyphicon-bin"></span> ' . Yii::t('form', 'Delete'),
                    ['delete', 'id' => $formModel->id],
                    [
                        'title' => Yii::t('form', 'Delete'),
                        'class' => 'btn btn-sm btn-danger',
                        'data' => [
                            'confirm' => Yii::t('form', 'Are you sure you want to delete this form? All stats, submissions, conditional rules and reports data related to this item will be deleted. This action cannot be undone.'),
                            'method' => 'post',
                        ],
                    ]
                ) ?>
            </div>
        <?php endif; ?>
    </div>

    <?= DetailView::widget([
        'model' => $formModel,
        'condensed' => false,
        'hover' => true,
        'mode' => DetailView::MODE_VIEW,
        'hideIfEmpty' => true,
        'enableEditMode' => false,
        'options' => [
            'class' => 'kv-view-mode', // Fix hideIfEmpty if enableEditMode is false
        ],
        'attributes' => [
            [
                'group' => true,
                'label' => Yii::t('form', 'Form Info'),
                'rowOptions' => ['class' => 'info']
            ],
            'id',
            'name',
            [
                'attribute' => 'language',
                'value' => $formModel->languageLabel,

            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => ($formModel->status === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'save',
                'format' => 'raw',
                'value' => ($formModel->save === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'analytics',
                'format' => 'raw',
                'value' => ($formModel->analytics === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'use_password',
                'format' => 'raw',
                'value' => ($formModel->use_password === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'novalidate',
                'format' => 'raw',
                'value' => ($formModel->novalidate === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'autocomplete',
                'format' => 'raw',
                'value' => ($formModel->autocomplete === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'resume',
                'format' => 'raw',
                'value' => ($formModel->resume === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'honeypot',
                'format' => 'raw',
                'value' => ($formModel->honeypot === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'recaptcha',
                'format' => 'raw',
                'value' => ($formModel->recaptcha === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'total_limit',
                'format' => 'raw',
                'value' => ($formModel->total_limit === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'ip_limit',
                'format' => 'raw',
                'value' => ($formModel->ip_limit === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'schedule',
                'format' => 'raw',
                'value' => ($formModel->schedule === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
            ],
            [
                'attribute' => 'author',
                'value' => $formModel->author->username,
                'label' => Yii::t('form', 'Created by'),
            ],
            [
                'attribute' => 'created_at',
                'value' => isset($formModel->created_at) ?
                    Carbon::createFromTimestampUTC($formModel->created_at)->diffForHumans() : null,
                'label' => Yii::t('form', 'Created'),
            ],
            [
                'attribute' => 'lastEditor',
                'value' => isset($formModel->lastEditor, $formModel->lastEditor->username) ? $formModel->lastEditor->username : null,
                'label' => Yii::t('form', 'Last Editor'),
            ],
            [
                'attribute' => 'updated_at',
                'value' => isset($formModel->updated_at) ?
                    Carbon::createFromTimestampUTC($formModel->updated_at)->diffForHumans() : null,
                'label' => Yii::t('form', 'Last updated'),
            ],
            [
                'group' => true,
                'label' => Yii::t('form', 'Confirmation Info'),
                'rowOptions' => ['class' => 'info']
            ],
            [
                'attribute' => 'formConfirmation',
                'value' => $formModel->formConfirmation->getTypeLabel(),
                'label' => Yii::t('form', 'How to'),
            ],
            [
                'attribute' => 'formConfirmation',
                'value' => Html::encode($formModel->formConfirmation->message),
                'label' => Yii::t('form', 'Message'),
            ],
            [
                'attribute' => 'formConfirmation',
                'value' => Html::encode($formModel->formConfirmation->url),
                'label' => Yii::t('form', 'Url'),
            ],
            [
                'attribute' => 'formConfirmation',
                'format' => 'raw',
                'value' => ($formModel->formConfirmation->send_email === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
                'label' => Yii::t('form', 'Send Email'),
            ],
            [
                'attribute' => 'formConfirmation',
                'value' => Html::encode($formModel->formConfirmation->mail_from),
                'label' => Yii::t('form', 'Reply To'),
            ],
            [
                'attribute' => 'formConfirmation',
                'value' => Html::encode($formModel->formConfirmation->mail_subject),
                'label' => Yii::t('form', 'Subject'),
            ],
            [
                'attribute' => 'formConfirmation',
                'value' => Html::encode($formModel->formConfirmation->mail_from_name),
                'label' => Yii::t('form', 'Name or Company'),
            ],
            [
                'attribute' => 'formConfirmation',
                'format' => 'raw',
                'value' => ($formModel->formConfirmation->mail_receipt_copy === 1) ?
                    '<span class="label label-success"> ' . Yii::t('form', 'ON') . ' </span>' :
                    '<span class="label label-danger"> ' . Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
                'label' => Yii::t('form', 'Includes a Submission Copy'),
            ],
            [
                'group' => true,
                'label' => Yii::t('form', 'Notification Info'),
                'rowOptions' => ['class' => 'info']
            ],
            [
                'attribute' => 'formEmail',
                'format' => 'raw',
                'value' => (!empty($formModel->formEmail->to)) ?
                    '<span class="label label-success"> ' . Yii::t('form', 'ON') . ' </span>' :
                    '<span class="label label-danger"> ' . Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
                'label' => Yii::t('form', 'Send Email'),
            ],
            [
                'attribute' => 'formEmail',
                'value' => Html::encode($formModel->formEmail->subject),
                'label' => Yii::t('form', 'Subject'),
            ],
            [
                'attribute' => 'formEmail',
                'value' => Html::encode($formModel->formEmail->to),
                'label' => Yii::t('form', 'Recipient'),
            ],
            [
                'attribute' => 'formEmail',
                'value' => Html::encode($formModel->formEmail->cc),
                'label' => Yii::t('form', 'Cc'),
            ],
            [
                'attribute' => 'formEmail',
                'value' => Html::encode($formModel->formEmail->bcc),
                'label' => Yii::t('form', 'Bcc'),
            ],
            [
                'attribute' => 'formEmail',
                'value' => $formModel->formEmail->typeLabel,
                'label' => Yii::t('form', 'Contents'),
            ],
            [
                'attribute' => 'formEmail',
                'format' => 'raw',
                'value' => ($formModel->formEmail->attach === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
                'label' => Yii::t('form', 'Attach'),
            ],
            [
                'attribute' => 'formEmail',
                'format' => 'raw',
                'value' => ($formModel->formEmail->plain_text === 1) ? '<span class="label label-success"> ' .
                    Yii::t('form', 'ON') . ' </span>' : '<span class="label label-danger"> ' .
                    Yii::t('form', 'OFF') . ' </span>',
                'type' => DetailView::INPUT_SWITCH,
                'widgetOptions' => [
                    'pluginOptions' => [
                        'onText' => Yii::t('form', 'ON'),
                        'offText' => Yii::t('form', 'OFF'),
                    ]
                ],
                'label' => Yii::t('form', 'Only Plain Text'),
            ],
        ],
    ]) ?>

</div>
