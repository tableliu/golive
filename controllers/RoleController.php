<?php

namespace app\controllers;

use app\components\Settings;
use Yii;
use mdm\admin\components\Configs;
use app\models\AuthItem;
use app\models\AuthItemSearch;
use app\common\RestResult;
use app\common\Consts;
use yii\web\NotFoundHttpException;
use yii\base\NotSupportedException;
use yii\filters\VerbFilter;
use yii\rbac\Item;
use app\common\IIPActiveController;
use mdm\admin\components\Helper;

class RoleController extends IIPActiveController
{
    public $modelClass = 'app\models\AuthItem';

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

    /**
     * Lists all AuthItem models.
     * @return mixed
     */
    public function actionIndex()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $searchModel = new AuthItemSearch(['type' => $this->type]);
        array_key_exists("pid", Yii::$app->request->getQueryParams()) !== false ? $pid = Yii::$app->request->getQueryParams()['pid'] : $pid = null;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams(), $pid);
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        $perpage = $dataProvider->getCount();  //在当前页获取数据项的数目
        $count = $dataProvider->getTotalCount();  // 获取所有页面的数据项的总数
        $result->code = Consts::REST_OK;
        $result->data = [
            "paging" => [
                'page' => $page,
                'per-page' => $perpage,
                'count' => $count
            ],
            "result" => array_values($dataProvider->getModels()),
            //"sort" => $sort,


        ];
        $result->msg = Yii::t('app', 'successfully');
        return $result;
    }

    /**
     * Displays a single AuthItem model.
     * @param  string $id
     * @return mixed
     */
    public function actionView($id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $auth = Configs::authManager();
        $item = $this->type === Item::TYPE_ROLE ? $auth->getRole($id) : $auth->getPermission($id);
        $result->data = [
            "model" => $item,
        ];
        $result->msg = Yii::t('app', 'successfully');
        return $result;
    }


    public function actionCreate()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $pid = Yii::$app->getRequest()->post()['pid'];
        Yii::$app->authManager->db = Settings::getProjectDB($pid);
        $model = new AuthItem(null);
        $model->type = $this->getType();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            $result->code = Consts::REST_OK;
            $result->data = [
                "id" => $model->name
            ];
            $result->msg = Yii::t('app', 'successfully');
        } else {
            if ($model->hasErrors()) {
                $result->code = Consts::REST_MODEL_ERROR;
                $result->errdata = $model->getJsonFineErrors();
                $result->msg = Yii::t('app', 'failure');
            }
        }
        return $result;
    }

    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {

        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $pid = Yii::$app->request->queryParams['pid'];
        $model = $this->findModel($id, $pid);
        $model->type = $this->getType();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->save()) {
            $result->code = Consts::REST_OK;
            $result->data = [
                "id" => $model->name,
            ];
            $result->msg = Yii::t('app', 'successfully');
        } else {
            if ($model->hasErrors()) {
                $result->code = Consts::REST_MODEL_ERROR;
                $result->errdata = $model->getJsonFineErrors();
                $result->msg = Yii::t('app', 'failure');
            }
            $result->code = Consts::REST_OK;
            $result->data = [
                "model" => $model,
            ];
            $result->msg = Yii::t('app', 'successfully');

        }
        return $result;


    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id, Yii::$app->getRequest()->post()['pid']);
        Yii::$app->authManager->remove($model->item);
        Helper::invalidate();

        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => "",
                'msg' => "",
            ]);
        $result->msg = Yii::t('app', 'successfully');
        return $result;
    }

    /**
     * Assign items
     * @param string $id
     * @return array
     */
    public function actionAssign($id, $pid)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => "",
                'msg' => "",
            ]);
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        $model = $this->findModel($id, $pid);
        $models = $model->getItems($pid);
        $result->data = [
            "paging" => [
                'page' => $page,
                'per-page' => $models['perpage'],
                'count' => $models['count'],
            ],
            "result" => $models['av'],
            //"sort" => $sort,


        ];
        $result->msg = Yii::t('app', 'successfully');
        return $result;


    }

    /**
     * Assign or remove items
     * @param string $id
     * @return array
     */
    public function actionRemove($id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $status = Yii::$app->getRequest()->post('status');
        $items = Yii::$app->getRequest()->post('items', []);
        $pid = Yii::$app->getRequest()->post('pid');
        $model = $this->findModel($id, $pid);
        if ($status == 1) {
            $success = $model->addChildren($items, $pid);
        } else {
            $success = $model->removeChildren($items, $pid);
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
                    'errcode' => Consts::REST_MODEL_ERROR,
                    'data' => "",
                    'msg' => Yii::t('app', 'failure'),
                ]);
        }
    }


    /**
     * Type of Auth Item.
     * @return integer
     */
    public function getType()
    {
        return Item::TYPE_ROLE;
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @param $pid
     * @return AuthItem the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id, $pid)
    {
        $auth = Yii::$app->authManager;
        $auth->db = Settings::getProjectDB($pid);
        $item = $this->type === Item::TYPE_ROLE ? $auth->getRole($id) : $auth->getPermission($id);
        if ($item) {
            return new AuthItem($item);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }


}
