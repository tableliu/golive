<?php

use kartik\datecontrol\Module as DateControlModule;
use yii\web\Response;

$config = [
    'id' => 'iip',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log', 'app\modules\easyforms\components\Bootstrap'],
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        "@mdm/admin" => "@vendor/mdmsoft/yii2-admin",
    ],
    'modules' => [
        'form' => ['class' => 'app\modules\easyforms\Module'],
        'onsite' => ['class' => 'app\modules\onsite\Module'],
    ],
    'components' => [
        'request' => [
            'cookieValidationKey' => 'e3cf9bb4-63cc-41c6-ab94-29faa43dd69a',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
            'csrfParam' => 'qscsrf',
        ],
        // 'response' => ['format' => 'json'],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'class' => 'app\components\User',
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            //'loginDuration' => 2592000,   //1 month
            'on afterLogin' => function ($event) {
                $user = $event->identity; //这里的就是User Model的实例
                $user->updateLoginMeta();
            }
        ],
        'errorHandler' => [
            'class' => 'app\components\rest\ErrorHandler'
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'user_db' => require __DIR__ . '/user_db.php',
        'gii_db' => require(__DIR__ . '/gii_db.php'),
        'mongodb' => require(__DIR__ . '/mongodb.php'),
        'session' => [
            'class' => 'yii\mongodb\Session',
            'sessionCollection' => 'session',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            //'enableStrictParsing' => false,
            'showScriptName' => false,
            'rules' => [
                ['class' => 'yii\rest\UrlRule', 'controller' => 'form/rules'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'form/submissions'],
            ],
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'fileMap' => [
                        'main' => 'app.php',
                    ],
                ],
            ],
        ],
        //        "authManager" => [
        //            "class" => 'yii\rbac\DbManager',
        //        ],
        'authManager' => [
            'class' => 'app\components\DbManager',
        ],
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null,
                    'basePath' => '@webroot',
                    'baseUrl' => '@web/static_files/',
                    'js' => [
                        'js/libs/jquery.js', // v1.11.2
                    ]
                ],
                'yii\bootstrap\BootstrapAsset' => [
                    'sourcePath' => null,
                    'basePath' => '@webroot',
                    'baseUrl' => '@web/static_files/',
                    'css' => [
                        'css/fonts.min.css',
                        'css/bootstrap.min.css', // v3.3.5
                    ]
                ],
                'yii\bootstrap\BootstrapPluginAsset' => [
                    'sourcePath' => null,
                    'basePath' => '@webroot',
                    'baseUrl' => '@web/static_files/',
                    'js' => [
                        'js/libs/bootstrap.min.js', // v3.3.5
                    ],
                ],
            ],
        ],
        'queueCloseRoomDelay' => require(__DIR__ . '/close_room_delay_mq.php'),
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
        'formsettings' => [
            'class' => 'app\modules\easyforms\components\Settings'
        ],
        'formuser' => [
            'class' => 'app\modules\easyforms\components\User'
        ],
        'services' => [
            'class' => 'app\components\ServiceContainer',
            'components' => require(__DIR__ . '/services.php'),
        ],
    ],
    'as cors' => [
        'class' => 'yii\filters\Cors',
        'cors' => require __DIR__ . '/cors.php'
    ],
    'as contentNegotiator' => [
        'class' => 'yii\filters\ContentNegotiator',
        'formats' => [
            'application/json' => Response::FORMAT_JSON,
            'application/xml' => Response::FORMAT_XML,
            'text/html' => Response::FORMAT_HTML,
        ],
    ],
    'as access' => [
        'class' => 'app\filters\RestAccessControl',
        'allowActions' => [
            'gii/*',
            'site/login',
            'site/logout',
            'user/login',
            'user/logout',
            'user/try',
            'user/try-session',
            'explain/show-doc',
            'project/index',
            'project/change',
            'site/*',
            'onsite/*',
            'cos/*'
        ]
    ],

    'params' => require __DIR__ . '/params.php',
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'app\components\IipDebugModule',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1'],
        'generators' => [
            'model' =>
                [
                    'class' => 'yii\gii\generators\model\Generator',
                    'db' => 'gii_db'
                ],
            'mongoDbModel' => [
                'class' => 'yii\mongodb\gii\model\Generator'
            ],
            'job' => [
                'class' => \yii\queue\gii\Generator::class,
            ],
        ]
    ];
}

return $config;
