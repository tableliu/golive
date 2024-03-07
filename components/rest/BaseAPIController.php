<?php

namespace app\components\rest;

use yii\rest\ActiveController;


class BaseAPIController extends ActiveController
{
    public $modelClass = '';
    public function behaviors()
    {
        $behaviors['authenticator'] = [
            'class' => RestHttpBearerAuth::className(),
        ];
        $behaviors['access'] = [
            'class' => 'app\components\filters\RestAccessControl',
            'allowActions' => [
                ''
            ]
        ];

        return $behaviors;
    }

    public function actions()
    {
        return [];
    }
}