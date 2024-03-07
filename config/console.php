<?php

$config = [
    'id' => 'basic-console',
    'timeZone' => 'Asia/Shanghai',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'queueCloseRoomDelay'],
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
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
        'queueCloseRoomDelay' => require(__DIR__ . '/close_room_delay_mq.php'),
        'settings' => [
            'class' => 'app\components\ConsoleSettings'
        ],

    ],
    'params' => require __DIR__ . '/params.php',
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
