<?php

use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use kartik\sidenav\SideNav;
use app\modules\easyforms\helpers\Language;
use app\modules\easyforms\components\widgets\Alert;
use app\modules\easyforms\components\widgets\SessionTimeout;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);

$moduleID = $this->context->module->id;
$controllerID = $this->context->id;
$actionID = $this->context->action->id;

// Brand
$appName = Yii::$app->formsettings->get("app.name");
$brandLabel = Html::tag("span", $appName, ["class" => "app-name"]);
$brandStyle = 'padding: 15px';
if ($logo = Yii::$app->formsettings->get("logo", "app", null)) {
    $brandLabel = Html::img(Yii::getAlias('@web/' . $logo), [
        'height' => '40px',
        'alt' => $appName,
        'title' => $appName,
    ]);
    $brandStyle = 'padding: 5px 15px';
}

// Session Timeout
$timeoutValue = (int)Yii::$app->formuser->preferences->get('App.User.SessionTimeout.value');
$timeoutWarning = empty(Yii::$app->params['App.User.SessionTimeout.warning']) ? $timeoutValue : ($timeoutValue - (int)Yii::$app->params['App.User.SessionTimeout.warning']);
?>
<?php $this->beginPage() ?>
    <!DOCTYPE html>
    <html lang="<?= Yii::$app->language ?>" dir="<?php echo Language::dir(); ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="generator" content="<?= Yii::$app->name ?> <?= Yii::$app->version ?>"/>
        <link rel="shortcut icon" href="<?= Yii::$app->getHomeUrl() ?>favicon.ico" type="image/x-icon">
        <link rel="icon" href="<?= Yii::$app->getHomeUrl() ?>favicon_32.png" sizes="32x32">
        <link rel="icon" href="<?= Yii::$app->getHomeUrl() ?>favicon_48.png" sizes="48x48">
        <link rel="icon" href="<?= Yii::$app->getHomeUrl() ?>favicon_96.png" sizes="96x96">
        <link rel="icon" href="<?= Yii::$app->getHomeUrl() ?>favicon_144.png" sizes="144x144">
        <?= Html::csrfMetaTags() ?>
        <title><?= Html::encode($this->title) . ' | ' . Yii::$app->formsettings->get('app.name') ?></title>
        <?php $this->head() ?>
    </head>
    <body class="admin-page <?= $this->context->action->id ?>">

    <?php $this->beginBody() ?>
    <div class="wrap">
        <?php if (!Yii::$app->user->isGuest) : ?>
            <?php NavBar::begin([
                'brandLabel' => 'easyform',
                'brandOptions' => [
                    'title' => Yii::$app->formsettings->get("app.description"),
                    'style' => $brandStyle,
                ],
                'brandUrl' => Yii::$app->homeUrl,
                'options' => [
                    'class' => 'navbar-inverse navbar-fixed-top',
                ],
            ]);
            echo Nav::widget([
                'options' => ['class' => 'navbar-nav navbar-right'],
                'items' => [
                    ['label' => 'Forms', 'url' => ['/form/form']],
                    ['label' => 'Themes', 'url' => ['/form/theme']],
                    ['label' => 'Settings', 'url' => ['/form/settings/site']],
                    Yii::$app->user->isGuest ? (
                    ['label' => 'Login', 'url' => ['/site/login']]
                    ) : (
                        '<li>'
                        . Html::beginForm(['/site/logout'], 'post')
                        . Html::submitButton(
                            'Logout (' . Yii::$app->user->identity->username . ')',
                            ['class' => 'btn btn-link logout']
                        )
                        . Html::endForm()
                        . '</li>'
                    )
                ],
            ]);
            ?>



            <?php NavBar::end(); ?>

            <div class="container">
                <?= Breadcrumbs::widget([
                    'options' => ['class' => 'breadcrumb breadcrumb-arrow'],
                    'itemTemplate' => "<li>{link}</li>\n", // template for all links
                    'activeItemTemplate' => "<li class='active'><span>{link}</span></li>\n",
                    'homeLink' => [
                        'label' => Yii::t('app', 'Dashboard'),
                        'url' => ['/dashboard'],
                    ],
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ]) ?>
                <?= Alert::widget() ?>
                <div class="row">
                    <div class="col-sm-3">

                            <?php echo SideNav::widget([
                                'type' => SideNav::TYPE_DEFAULT,
                                'heading' => '<i class="glyphicon glyphicon-cogwheel"></i> ' . Yii::t('app', 'Settings & Tools'),
                                'iconPrefix' => 'glyphicon glyphicon-',
                                'items' => [
                                    [
                                        'url' => Url::to(['settings/site']),
                                        'label' => Yii::t("app", "Site settings"),
                                        'icon' => 'cogwheels',
                                        'active' => ($actionID == 'site'),
                                    ],
                                    [
                                        'url' => Url::to(['settings/mail']),
                                        'label' => Yii::t("app", "Mail Server"),
                                        'icon' => 'inbox-out',
                                        'active' => ($actionID == 'mail'),
                                    ],
                                    [
                                        'url' => Url::to(['settings/import-export']),
                                        'label' => Yii::t("app", "Import / Export"),
                                        'icon' => 'sorting',
                                        'active' => ($actionID == 'import-export'),
                                    ],
                                    [
                                        'url' => Url::to(['settings/performance']),
                                        'label' => Yii::t("app", "Performance"),
                                        'icon' => 'settings',
                                        'active' => ($actionID == 'performance'),
                                    ],
                                ],
                            ]); ?>

                    </div>
                    <div class="col-sm-9">
                        <?= $content ?>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <div class="container">
                    <p class="pull-right">&copy; <?= Yii::$app->formsettings->get("app.name") ?> <?= date('Y') ?></p>
                </div>
            </footer>
        <?php endif; ?>
    </div>

    <?php $this->endBody() ?>

    <?php if ($timeoutValue > 0): ?>
        <?= SessionTimeout::widget([
            'warnAfter' => $timeoutWarning,
            'redirAfter' => $timeoutValue,
        ]) ?>
    <?php endif; ?>

    </body>
    </html>
<?php $this->endPage() ?>