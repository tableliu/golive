<?php


$params = require __DIR__ . '/params.php';

$config = [
    'id'=>'form',
    'basePath' => dirname(__DIR__),
    'defaultRoute' => '',
    'bootstrap' => [],
   // 'language' => 'zh-CN',
    'modules' => [
//        'admin' => [
//            'class' => 'app\modules\easyforms\Module',
//        ],
        'gridview' => ['class' => 'kartik\grid\Module'],
        'datecontrol' => ['class' => 'kartik\datecontrol\Module'],
    ],
    'components' => [
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule', 'controller' => 'form/rules'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'form/submissions'],

            ],
        ],
//        'mailer' => [
//            'class' => 'yii\swiftmailer\Mailer',
//            'viewPath' => '@app/modules/easyforms/mail', //指定邮件模版路径
//        ],
        'user' => [
            'class' => 'app\modules\easyforms\components\User',
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            //'loginDuration' => 2592000,   //1 month
            'on afterLogin' => function ($event) {
                $user = $event->identity; //这里的就是User Model的实例
                $user->updateLoginMeta();
            }
        ],
//        'i18n' => [
//            'class' => 'yii\i18n\PhpMessageSource',
//            'translations' => [
//                'form' => [
//                    'language' => 'zh-CN',
//                    'basePath' => '@app/modules/easyforms/messages',
//                    'sourceLanguage' => 'zh-CN',
//                    'fileMap' => [
//                        'app' => 'app.php',
//                        'app/error' => 'error.php',
//                    ],
//                ],
//            ],
//        ],
    ],
    'params' => $params,

];
//if (YII_ENV_DEV) {
//    // configuration adjustments for 'dev' environment
//    $config['bootstrap'][] = 'debug';
//    $config['modules']['debug'] = [
//        'class' => 'yii\debug\Module',
//        // uncomment the following to add your IP if you are not connecting from localhost.
//        'allowedIPs' => ['192.168.3.20', '::1'],
//    ];
//
//    $config['bootstrap'][] = 'gii';
//    $config['modules']['gii'] = [
//        'class' => 'yii\gii\Module',
//        // uncomment the following to add your IP if you are not connecting from localhost.
//        //'allowedIPs' => ['127.0.0.1', '::1'],
//    ];
//}


return $config;
