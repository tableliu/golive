<?php

namespace app\controllers;

use Yii;
use yii\db\Query;
use yii\filters\VerbFilter;
use app\common\IIPActiveController;
use app\common\RestResult;
use app\common\Consts;
use yii\helpers\ArrayHelper;


/**
 * ApparatusController implements the CRUD actions for Apparatus model.
 */
class AuthController extends IIPActiveController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [],
        ];
        return $behaviors;
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionGetAuth()
    {
        $models = (new Query())->select(['item_name'])
            ->from('auth_assignment')
            ->where(['user_id' => Yii::$app->user->identity->id])
            ->all();
        $array = ArrayHelper::getColumn($models, 'item_name');
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $array,
                'msg' => Yii::t('app', 'successfully')
            ]);

    }

}
