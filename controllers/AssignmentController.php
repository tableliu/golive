<?php

namespace app\controllers;

use Yii;
use app\models\Assignment;
use mdm\admin\models\searchs\Assignment as AssignmentSearch;
use app\common\IIPActiveController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use app\common\RestResult;
use app\common\Consts;
use yii\data\ArrayDataProvider;

/**
 * AssignmentController implements the CRUD actions for Assignment model.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AssignmentController extends IIPActiveController
{
    public $modelClass = 'app\models\Assignment';
    public $userClassName;
    public $idField = 'id';
    public $usernameField = 'username';
    public $fullnameField;
    public $searchClass;
    public $extraColumns = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if ($this->userClassName === null) {
            $this->userClassName = Yii::$app->getUser()->identityClass;
            $this->userClassName = $this->userClassName ?: 'mdm\admin\models\User';
        }
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'revoke' => ['post'],
            ],

        ];
        return $behaviors;
    }


    public function actionGetAssign ($id, $pid)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => null,
                'msg' => "",
            ]);
        $model = new Assignment($id);
        $model = $model->getItems($pid);
        if ($model['available']) {
            foreach ($model['available'] as $key => $value) {
                $av[] = [
                    'value' => $key,
                    'type' => $value,
                    'status' => 0
                ];
            }
        } else {
            $av = [];
        }
        if ($model['assigned']) {
            foreach ($model['assigned'] as $key => $value) {
                $as[] = [
                    'value' => $key,
                    'type' => $value,
                    'status' => 1
                ];
            }
        } else {
            $as = [];
        }
        $rawData = array_merge($as, $av);
        $params = Yii::$app->request->getQueryParams();
        array_key_exists("per-page", Yii::$app->request->getQueryParams()) !== false ? $rows_perpage = Yii::$app->request->getQueryParams()['per-page'] : $rows_perpage = Yii::$app->params['GridView.pagination.pageSize'];
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        if ($params) {
            array_key_exists("value", $params) !== false ? $value = $params['value'] : $value = "";
            array_key_exists("status", $params) !== false ? $status = $params['status'] : $status = "";
            foreach ($rawData as $name => $item) {
                $f = (empty($value) || mb_strpos(mb_strtolower($item['value']), $params['value']) !== false) &&
                    (empty($status) || $item['status'] == $params['status']);
                if (!$f) {
                    unset($rawData[$name]);
                }
            }
        }
        $dataProvider = new ArrayDataProvider([
            'allModels' => $rawData,
            'pagination' => [
                'pageSize' => isset(Yii::$app->request->getQueryParams()['per-page']) ? Yii::$app->request->getQueryParams()['per-page'] : Yii::$app->params['GridView.pagination.pageSize'],
            ],

        ]);
        $Data = array_values($dataProvider->getModels());
        $perpage = $dataProvider->getCount();
        $count = $dataProvider->getTotalCount();
        $result->code = Consts::REST_OK;
        $result->data = [
            "paging" => [
                'page' => $page,
                'per-page' => $perpage,
                'count' => $count,
                'rows_perpage' => $rows_perpage
            ],
            "result" => $Data
        ];
        $result->msg = Yii::t('app', 'successfully');
        return $result;


    }

    /**
     * Assign items
     * @param string $id
     * @return array
     */
    public function actionRevoke($id)
    {

        $items = Yii::$app->getRequest()->post('items', []);
        $pid = Yii::$app->getRequest()->post('pid');
        $status = Yii::$app->getRequest()->post('status');
        $model = new Assignment($id);
        if ($status == 1) {
            $success = $model->assign($items, $pid);
        } else {
            $success = $model->revoke($items, $pid);
        }

        if ($success) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => "",
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        } else {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => null,
                    'msg' => Yii::t('app', 'failure'),
                ]);
        }

        return array_merge($model->getItems(), ['success' => $success]);
    }


}
