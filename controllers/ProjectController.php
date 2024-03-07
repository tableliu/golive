<?php

namespace app\controllers;

use app\common\Consts;
use app\common\IIPActiveController;
use app\common\RestResult;
use app\components\Settings;
use app\components\User;
use app\models\Project;
use app\models\ProjectUser;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ProjectController implements the CRUD actions for Project model.
 */
class ProjectController extends IIPActiveController
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'delete' => ['post'],
                'update' => ['post'],
            ],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $user_id = Yii::$app->user->id;
        $array = Project::search($user_id);
        $value = Settings::getCurrentPid();
//        if (count($array) == 1 && empty($value)) {
//            $value = $array[0]['pid'];
//            Settings::setUserLastPid($value);
//        }
        foreach ($array as $key => $v) {
            $array[$key]['pid'] == $value ? $array[$key]['select'] = true : $array[$key]['select'] = false;

        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $array,
                'msg' => Yii::t('app', 'successfully'),
            ]);
    }

    //切换数据库
    public function actionChange($pid)
    {

        if (!ProjectUser::getProject($pid, Yii::$app->user->id)) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => "",
                    'msg' => "Switching project failed",
                ]);
        }
//        Yii::$app->getSession()->set(Settings::SETTINGS_CACHE_KEY_DB, null);
        Settings::setUserLastPid($pid);
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => Yii::$app->getResponse()->getCookies(),
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }


    public function actionGetProject()
    {
        $array = Project::ProjectIndex();
        $newarray = [];
        foreach ($array as $key => $v) {
            empty($array[$key]['change']) ? $array[$key]['select'] = false : $array[$key]['select'] = true;
            unset($array[$key]['change']);
            $newarray = $array;

        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $newarray,
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }

    public function actionUserProject($user_id)
    {
        $array = Project::search($user_id);
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $array,
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }

}
