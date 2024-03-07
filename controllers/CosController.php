<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/11
 * Time: 17:07
 */

namespace app\controllers;


use app\common\Consts;
use app\common\IIPActiveController;
use app\common\RestResult;
use app\helpers\CosHelper;
use Yii;
use yii\filters\VerbFilter;

class CosController extends IIPActiveController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'temp-key' => ['post'],
            ],
        ];
        return $behaviors;
    }

    public function actionTempKey()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);

        try {
            $type = Yii::$app->request->post('type');
            $exts = Yii::$app->request->post('exts');
            $user_id = Yii::$app->user->id;
            $result->code = Consts::REST_OK;
            $result->data = CosHelper::getTempKeysBulk($user_id, $type, $exts);
            return $result;
        } catch (\Exception $e) {
            $result->msg = $e->getMessage();
            return $result;
        }
    }

}