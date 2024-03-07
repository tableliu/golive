<?php
$config =
    [
        'components' => [
            'user' => [
                'class' => 'app\modules\onsite\components\User',
                'identityClass' => 'app\models\User',
                'enableAutoLogin' => false,
                'enableSession' => false,
            ],
        ]
    ];

return $config;