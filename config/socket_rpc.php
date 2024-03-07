<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/2
 * Time: 13:40
 */

$config = [
    'id' => 'socket_pull_app',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Asia/Shanghai',
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\controllers\rpc',
    'modules' => [
        'admin' => [
            'class' => 'mdm\admin\Module',
        ],
        'rest' => ['class' => 'app\modules\rest\Module'],
        'onsite' => ['class' => 'app\modules\onsite\Module']
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'user_db' => require __DIR__ . '/user_db.php',
        'mongodb' => require(__DIR__ . '/mongodb.php'),
        'user' => [
            'class' => 'app\components\socket\user\WebUser',
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            //'loginDuration' => 2592000,   //1 month
        ],
        'authManager' => [
            'class' => 'app\components\DbManager',
        ],
        'formatter' => [
            'dateFormat' => 'yyyy-MM-dd',
            'datetimeFormat' => 'yyyy-MM-dd HH:mm:ss',
            'decimalSeparator' => ',',
            'thousandSeparator' => ' ',
            'currencyCode' => 'CNY',
        ],
        'settings' => [
            'class' => 'app\components\Settings'
        ],
        'request' => [
            'class' => 'app\components\socket\rpc\Request',
        ],
        'response' => [
            'class' => 'app\components\socket\rpc\Response',
        ],
        'errorHandler' => [
            'class' => 'app\components\ErrorHandler'
        ],
    ],
    'params' => require(__DIR__ . '/params.php'),
];

return $config;