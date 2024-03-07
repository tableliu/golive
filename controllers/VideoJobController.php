<?php

namespace app\controllers;

use app\common\Consts;
use app\common\RestResult;
use app\models\Job;
use app\models\Profile;
use app\models\VideoStepEdit;
use app\models\VideoStepEditSearch;
use app\modules\onsite\models\MobileJobInfo;
use Yii;
use app\models\VideoJob;
use app\models\VideoJobSearch;
use app\common\IIPActiveController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * VideoJobController implements the CRUD actions for VideoJob model.
 */
class VideoJobController extends IIPActiveController
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
            ],
        ];
        return $behaviors;
    }

    /**
     * Lists all VideoJob models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new VideoJobSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = $dataProvider->getModels();
        if ($model) {
            foreach ($model as $key => $val) {
                $newData[$key]['v_id'] = $val->id;
                $newData[$key]['status'] = $val->status;
                $newData[$key]['name'] = $val->name;
                $newData[$key]['remark'] = $val->remark;
                $newData[$key]['created_by'] = Profile::GetFullName($val->created_by);
                $newData[$key]['created_at'] = $val->created_at;
            }
        } else {
            $newData = "";
        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $newData,
                'msg' => Yii::t('app', 'successfully')
            ]);
    }


    public function actionView($id)
    {
    }

    /**
     * Creates a new VideoJob model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new VideoJob();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => ["id" => $model->id],
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        } else {
            if ($model->hasErrors()) {
                return Yii::createObject(
                    [
                        'class' => RestResult::className(),
                        'code' => Consts::REST_MODEL_ERROR,
                        'errdata' => $model->getJsonFineErrors(),
                        'msg' => Yii::t('app', 'failure')
                    ]);

            }
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => null,
                    'msg' => Yii::t('app', 'failure')
                ]);

        }
    }


    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => ["id" => $model->id],
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        } else {
            if ($model->hasErrors()) {
                return Yii::createObject(
                    [
                        'class' => RestResult::className(),
                        'code' => Consts::REST_MODEL_ERROR,
                        'errdata' => $model->getJsonFineErrors(),
                        'msg' => Yii::t('app', 'failure')
                    ]);

            }
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => null,
                    'msg' => Yii::t('app', 'failure')
                ]);

        }
    }

    public function actionDelete($id)
    {
        if (MobileJobInfo::findone(['v_id' => $id])) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => null,
                    'msg' => Yii::t('app', 'Delete failed, video job record exists')
                ]);

        }
        if (VideoStepEdit::findone(['v_id' => $id])) {
            VideoStepEdit::deleteAll(['v_id' => $id]);
        }
        if (VideoJob::deleteAll(['id' => $id]) !== false) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => ["id" => $id],
                    'msg' => Yii::t('app', 'successfully')
                ]);
        }

        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => null,
                'msg' => Yii::t('app', 'successfully')
            ]);


    }


    protected function findModel($id)
    {
        if (($model = VideoJob::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public function actionStepIndex()
    {

        $searchModel = new VideoStepEditSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = $dataProvider->getModels();
        if ($model) {
            foreach ($model as $key => $val) {
                $newData[$key]['id'] = $val->id;
                $newData[$key]['step'] = $val->step;
                $newData[$key]['title'] = $val->title;
                $newData[$key]['content'] = $val->content;
            }
        } else {
            $newData = "";
        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $newData,
                'msg' => Yii::t('app', 'successfully')
            ]);

    }

    public function actionStepSave()
    {

        $v_id = Yii::$app->request->post()['v_id'];
        $step = Yii::$app->request->post()['step'];
        $oldstep = VideoStepEdit::findone(['step' => $step, 'v_id' => $v_id]);
        if ($oldstep) {
            $VideoStepEditmodel = VideoStepEdit::find()->where(['v_id' => $v_id])->andWhere(['>=', 'step', $step])->asArray()->all();
            foreach ($VideoStepEditmodel as $key => $v) {
                $model = VideoStepEdit::findOne(['id' => $v['id']]);
                $step = $model['step'] + 1;
                $model->step = $step;
                $model->save();
            }
        }
        $model = new VideoStepEdit();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => ["id" => $model->id],
                    'msg' => Yii::t('app', 'successfully')
                ]);
        } else {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_MODEL_ERROR,
                    'errdata' => $model->getJsonFineErrors(),
                    'msg' => $model->getErrors(),
                ]);
        }

    }

    public function actionStepUpdate($id)
    {

        $model = VideoStepEdit::findOne($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => ["id" => $model->id],
                    'msg' => Yii::t('app', 'successfully')
                ]);
        } else {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_MODEL_ERROR,
                    'errdata' => $model->getJsonFineErrors(),
                    'msg' => $model->getErrors(),
                ]);
        }


    }

    public function actionStepDelete($id)
    {
        //mobile isexit
        $findModel = $this->StepfindModel($id);
        $v_id = $findModel['v_id'];
        if (MobileJobInfo::findone(['v_id' => $v_id])) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_MODEL_ERROR,
                    'data' => ["id" => $id],
                    'msg' => Yii::t('app', 'Delete failed, video job record exists')
                ]);

        } else {
            $this->StepfindModel($id)->delete();
            $step = $findModel['step'];
            $model = VideoStepEdit::find()->where(['v_id' => $v_id])->andWhere(['>', 'step', $step])->all();
            foreach ($model as $v) {
                $model = $this->StepfindModel($v['id']);
                $model->step = $v['step'] - 1;
                $model->save();
            }

        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => ["id" => $id],
                'msg' => Yii::t('app', 'successfully')
            ]);


    }

    protected function StepfindModel($id)
    {
        if (($model = VideoStepEdit::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
