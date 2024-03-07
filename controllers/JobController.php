<?php

namespace app\controllers;

use app\common\Consts;
use app\common\RestResult;
use app\models\VideoRecorder;
use app\models\JobSearch;
use app\models\Profile;
use app\models\VideoJob;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\FormSearch;
use app\modules\easyforms\models\FormSubmission;
use Matrix\Operators\Subtraction;
use Yii;
use app\common\IIPActiveController;
use yii\data\ArrayDataProvider;
use yii\data\Pagination;
use yii\db\Query;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * VideoJobController implements the CRUD actions for VideoJob model.
 */
class JobController extends IIPActiveController
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

    public function actionIndex()
    {
        $searchModel = new JobSearch();
        //form
        $formdataProvider = $searchModel->searchForm(Yii::$app->request->queryParams);
        $model = $formdataProvider->getModels();
        if ($model) {
            foreach ($model as $key => $val) {
                $newformdata[$key]['id'] = $val->id;
                $newformdata[$key]['form_id'] = $val->form_id;
                $newformdata[$key]['type'] = "form";
                $newformdata[$key]['name'] = $val->form->name;
                $newformdata[$key]['location'] = "";
                //$newData[$key]['remark'] = $val->remark;
                $newformdata[$key]['created_by'] = Profile::GetFullName($val->created_by);
                $newformdata[$key]['created_at'] = date('Y-m-d H:i:s', $val->created_at);

            }
        } else {
            $newformdata = [];
        }
        //video
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $model = $dataProvider->getModels();
        if ($model) {
            foreach ($model as $key => $val) {
                $newvideoData[$key]['id'] = $val->id;
                $newvideoData[$key]['type'] = "video";
                $newvideoData[$key]['name'] = $val->video->name;
                $newvideoData[$key]['location'] = $val->formatted_address;
                //$newData[$key]['remark'] = $val->remark;
                $newvideoData[$key]['created_by'] = Profile::GetFullName($val->created_by);
                $newvideoData[$key]['created_at'] = $val->created_at;

            }
        } else {
            $newvideoData = [];
        }

        if (array_key_exists("type", Yii::$app->request->getQueryParams()) && !empty(Yii::$app->request->queryParams['type'])) {
            $type = Yii::$app->request->queryParams['type'];
            if ($type == "video") $newformdata = [];
            if ($type == "form") $newvideoData = [];
        }
        $NewData = ArrayHelper::merge($newformdata, $newvideoData);
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        //分页
        $provider = new ArrayDataProvider([
            'allModels' => $NewData,
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => isset(Yii::$app->request->queryParams['per-page']) ? Yii::$app->request->queryParams['per-page'] : Yii::$app->params['GridView.pagination.pageSize'],
            ],
        ]);
        if (array_key_exists("sort", Yii::$app->request->getQueryParams())) {
            strpos(Yii::$app->request->getQueryParams()['sort'], '-') !== false ? $sort = substr(Yii::$app->request->getQueryParams()['sort'] . "|SORT_DESC", 1) : $sort = Yii::$app->request->getQueryParams()['sort'] . "|SORT_ASC";
        } else {
            $sort = "id|SORT_DESC";
        }
        $perpage = $provider->getCount();  //在当前页获取数据项的数目
        $count = $provider->getTotalCount();
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => [
                    "paging" => [
                        'page' => $page,
                        'per-page' => $perpage,
                        'count' => $count,
                        'sort' => $sort,


                    ],
                    "result" => array_values($provider->getModels()),
                ],
                'msg' => Yii::t('app', 'successfully')
            ]);
    }

    public function actionGetUser()
    {
        $videomodel = (new Query())
            ->from('video_record' . ' C')
            ->select(['A.user_id', 'A.full_name'])
            ->leftJoin(Profile::tableName() . ' A', 'C.created_by = A.user_id')
            ->distinct()
            ->all();
        $formmodel = (new Query())
            ->from('form_submission' . ' C')
            ->select(['A.user_id', 'A.full_name'])
            ->leftJoin(Profile::tableName() . ' A', 'C.created_by = A.user_id')
            ->distinct()
            ->all();
        $user_array = ArrayHelper::index(ArrayHelper::merge($videomodel, $formmodel), 'user_id');
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => array_values($user_array),
                'msg' => Yii::t('app', 'successfully')
            ]);

    }

    public function actionGetContent()
    {
        $Formmodel = Form::find()->select("name")->asArray()->all();
        if ($Formmodel) {
            foreach ($Formmodel as $key => $v) {
                $newFormmodel[$key]['type'] = "form";
                $newFormmodel[$key]['name'] = $v['name'];
            }
        }
        $videomodel = VideoJob::find()->select("name")->asArray()->all();
        if ($videomodel) {
            foreach ($videomodel as $key => $v) {
                $newvideomodel[$key]['type'] = "video";
                $newvideomodel[$key]['name'] = $v['name'];
            }
        }
        $newdate = array_merge($newFormmodel, $newvideomodel);
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $newdate,
                'msg' => Yii::t('app', 'successfully')
            ]);

    }

    public function actionView()
    {
        $type = Yii::$app->request->post('type');
        $id = Yii::$app->request->post('id');
        $newdata = [];
        if ($type == "video") {
            $model = $this->findModel($id);
            $jod_id = $model->job_id;
            $submissions_id = $model->submission_id;
            $formModel = VideoJob::findOne($jod_id);
            $submisssion = VideoRecorder::getData($submissions_id, $model->created_at);
            $newdata['video'] = $model->name;
            $newdata['step_content'] = count($submisssion);
            $newdata['location'] = $model->formatted_address;
            $newdata['longitude'] = $model->longitude;
            $newdata['latitude'] = $model->latitude;
            $newdata['title'] = $formModel->name;
            // $newdata['created_at']=$model->created_at;
            $newdata['start_time'] = $model->start_time;
            $newdata['end_time'] = $model->end_time;
            $newdata['created_by'] = Profile::GetFullName($model->created_by);
            $newdata['step_data'] = $submisssion;
        }
//        }else{
//            $FormSubmissionmodel=  FormSubmission::findOne(['id'=>$id]);
//            $data = $FormSubmissionmodel->data;
//            $formModel = Form::findOne(['id'=>$FormSubmissionmodel['form_id']]);
//            $formDataModel = $formModel->formData;
//            $fields = $formDataModel->getFieldsForSubmissions();
//            $newdata = [];
//            foreach ($fields as $field){
//                 $name =$field['name'];
//                 $array[$field['label']]=$data[$name];
//            }
//            $newdata['submisssion'] = $array;
//            $newdata['location']="";
//            $newdata['content']=$formModel->name;
//            $newdata['created_at']=date('Y-m-d H:i:s', $FormSubmissionmodel->created_at);
//            $newdata['created_by']= Profile::GetFullName($FormSubmissionmodel->created_by);
//        }


        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $newdata,
                'msg' => Yii::t('app', 'successfully')
            ]);

    }

    protected function findModel($id)
    {
        if (($model = VideoRecorder::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


}
