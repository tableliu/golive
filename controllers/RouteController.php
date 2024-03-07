<?php

namespace app\controllers;

use app\components\Settings;
use Yii;
use app\models\Route;
use app\common\IIPActiveController;
use yii\filters\VerbFilter;
use yii\data\ArrayDataProvider;
use app\common\RestResult;
use app\common\Consts;

/**
 * Description of RuleController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class RouteController extends IIPActiveController
{
    public $modelClass = 'mdm\admin\models\Route';
    public $value;
    public $status;

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'create' => ['post'],
                'assign' => ['post'],
                'remove' => ['post'],
                'refresh' => ['post'],
            ],
        ];
        return $behaviors;
    }

    public function actionIndex()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $params = Yii::$app->request->getQueryParams();
        array_key_exists("pid", Yii::$app->request->getQueryParams()) !== false ? $pid = Yii::$app->request->getQueryParams()['pid'] : $pid = null;
        $model = new Route();
        $models = $model->getRoutes($pid);
        $assigned = $models['assigned'];

        if ($assigned) {
            foreach ($assigned as $v) {
                $V[] = [
                    'status' => 1,
                    'value' => $v
                ];
            }
        } else {
            $V = [];

        }
        $available = $models['available'];
        if ($available) {
            foreach ($available as $v) {
                $K[] = [
                    'status' => 0,
                    'value' => $v
                ];
            }
        } else {
            $K = [];
        }

        $rawData = array_merge($V, $K);

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
                'pageSize' => (isset($params['iipsearch']) && !empty($params['iipsearch'])) ? '' : (isset($params['per-page']) ? $params['per-page'] : Yii::$app->params['GridView.pagination.pageSize']),
            ],

        ]);
        $Data = array_values($dataProvider->getModels());
        $perpage = $dataProvider->getCount();  //在当前页获取数据项的数目
        $count = $dataProvider->getTotalCount();  // 获取所有页面的数据项的总数
        $result->code = Consts::REST_OK;
        $result->data = [
            "paging" => [
                'page' => $page,
                'per-page' => $perpage,
                'count' => $count
            ],
            "result" => $Data
        ];
        $result->msg = Yii::t('app', 'successfully');
        return $result;
        //return $this->render('index', ['routes' => $model->getRoutes()]);
    }

    public function actionRemove()
    {
        $routes = Yii::$app->getRequest()->post('items', []);
        $pid = Yii::$app->getRequest()->post()['pid'];
        $status = Yii::$app->getRequest()->post('status');
        $model = new Route();
        if ($status == 1) {
            $model->addNew($routes, $pid);
        } else {
            $model->remove($routes, $pid);
        }
        return $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => "",
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }

}
