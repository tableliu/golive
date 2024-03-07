<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/22
 * Time: 16:49
 */

namespace app\modules\onsite\controllers;


use app\common\Consts;
use app\common\RestResult;
use app\components\rest\BaseAPIController;
use app\helpers\CosHelper;
use Yii;
use yii\filters\VerbFilter;

class CosController extends BaseAPIController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'temp-key' => ['POST'],
            ]
        ];

        return $behaviors;
    }

    public function actionTempKey()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);

        try {
            $type = Yii::$app->request->post('type');
            $exts = Yii::$app->request->post('exts');
            // 处理来自于form提交的内容
            if (is_string($exts))
                $exts = \yii\helpers\Json::decode($exts);
            if (empty($type) || empty($exts)) {
                $result->msg = 'no param';
                return $result;
            }
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