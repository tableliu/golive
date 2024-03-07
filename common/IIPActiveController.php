<?php

namespace app\common;

use yii\rest\ActiveController;


class IIPActiveController extends ActiveController
{
    public $enableCsrfValidation = false;
    public $modelClass = '';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        return $behaviors;
    }

    public function actions()
    {
        return [];
    }

    protected function verbs()
    {
        return [];
    }

}