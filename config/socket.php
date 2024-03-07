<?php

$config = [
    'id' => 'socket',
    'timeZone' => 'Asia/Shanghai',
    'language' => 'zh-CN',
    'sourceLanguage' => 'en-US',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'components' => [
        //        'request' => [
        //            'class' => 'app\components\socket\Request',
        //            'cookieValidationKey' => 'PEi6ICsok3vWiJSJJtQV2JZ6D-jk5gkh',
        //        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'errorHandler' => [
            'class' => 'app\components\ErrorHandler'
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 1,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logVars' => [],
                    'exportInterval' => 1
                ],
            ],
        ],
        'authManager' => [
            'class' => 'app\components\DbManager',
        ],
        'settings' => [
            'class' => 'app\components\Settings'
        ],
        'user_db' => require __DIR__ . '/user_db.php',
        'mongodb' => require(__DIR__ . '/mongodb.php'),
        //  'queueCloseRoomDelay' => require(__DIR__ . '/close_room_delay_mq.php'),
        //        'session' => [
        //            'class' => 'app\components\socket\Session',
        //            'sessionCollection' => 'session',
        //        ],
        //        'user' => [
        //            'class' => 'app\components\socket\WebUser',
        //            //'identityCookie' => ['name' => '_i_token', 'httpOnly' => true]
        //        ],
    ],
    'params' => require(__DIR__ . '/params.php'),
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
