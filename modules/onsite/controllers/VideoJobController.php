<?php

namespace app\modules\onsite\controllers;


use app\helpers\GeoCodingHelper;
use app\common\Consts;
use app\common\RestResult;
use app\components\rest\BaseAPIController;
use app\models\VideoJobTimeline;
use app\models\VideoRecorder;
use app\models\VideoStepEditSearch;
use app\modules\easyforms\helpers\SlugHelper;
use app\modules\onsite\models\MobileJobInfo;
use Yii;
use app\models\VideoJob;
use app\models\VideoJobSearch;
use yii\filters\ContentNegotiator;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * VideoJobController implements the CRUD actions for VideoJob model.
 */
class VideoJobController extends BaseAPIController
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
                'save' => ['post'],
            ],
        ];
        return $behaviors;
    }


    public function actionIndex()
    {
        $searchModel = new VideoJobSearch();
        $array =Yii::$app->request->queryParams;
        $array['status'] = 1;
        $dataProvider = $searchModel->search($array);
        $model = $dataProvider->getModels();
        if ($model) {
            foreach ($model as $key => $val) {
                $newData[$key]['v_id'] = $val->id;
                $newData[$key]['name'] = $val->name;
            }
        } else {
            $newData =[];
        }
        return  Yii::createObject(
         [
             'class' => RestResult::className(),
             'code' => Consts::REST_OK,
             'data' => $newData,
             'msg' => Yii::t('app', 'successfully')
         ]);
    }

    public function actionGetstep()
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
        return  Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $newData,
                'msg' => Yii::t('app', 'successfully')
            ]);

    }

    public function actionSave()
    {
        $submission_id ='';
        $v_id = Yii::$app->request->post()['v_id'];
        $step_data = Yii::$app->request->post()['step_data'];
        $db = MobileJobInfo::getDb();
        $transaction = $db->beginTransaction();
        try {
            if(!empty($step_data)){
                foreach ($step_data as $key => $v) {
                    $model =  new MobileJobInfo();
                    $model->timeline=$v['timeline'];
                    $model->s_id = $v['step_id'];
                    $model->v_id = $v_id;
                    $image_data  = $v['image_data'];
                    $model->save();
                    $submission_id .= $model->id .",";
                    $mobile_id = $model->id;
                    foreach ($image_data as $k => $data) {
                        $model= new VideoJobTimeline();
                        $model->image = $data['imagepath'];
                        $model->m_v_id = $mobile_id;
                        $model->image_timeline = $data['image_timeline'];
                        $model->content = $data['content'];
                        $model->save();
                    }

                }
                $Jobmodel = New VideoRecorder;
                $map_key = Yii::$app->params['Baidu.Map.Appkey'];
                $Jobmodel->job_id =$v_id;
                $longitude = Yii::$app->request->post()['longitude'];
                $latitude = Yii::$app->request->post()['latitude'];
                $array = GeoCodingHelper::getAddressComponent($map_key, $longitude, $latitude, GeoCodingHelper::NO_POIS);
                $Jobmodel->longitude = $longitude;
                $Jobmodel->latitude = $latitude;
                $Jobmodel->formatted_address = $array['result']['formatted_address'];
                $Jobmodel->submission_id =$submission_id;
                $Jobmodel->start_time = Yii::$app->request->post()['start_time'];
                $Jobmodel->end_time = Yii::$app->request->post()['end_time'];
                $Jobmodel->name = Yii::$app->request->post()['videopath'];
                $Jobmodel->extension = "";
                $Jobmodel->size = "";
                $Jobmodel->save();
            }
            $transaction->commit();
            return  Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => ["id"=>$Jobmodel->id],
                    'msg' => Yii::t('app', 'successfully')
                ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }




    }

    protected function findModel($id)
    {
        if (($model = VideoJob::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }



}
