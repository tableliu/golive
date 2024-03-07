<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/2
 * Time: 13:40
 */

$config = [
    'id' => 'socket_close_app',
    'basePath' => dirname(__DIR__),
    'timeZone' => 'Asia/Shanghai',
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'modules' => [
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
        'errorHandler' => [
            'class' => 'app\components\ErrorHandler'
        ],
        'queueCloseRoomDelay' => require(__DIR__ . '/close_room_delay_mq.php')
    ],
    'params' => require(__DIR__ . '/params.php'),
];

return $config;