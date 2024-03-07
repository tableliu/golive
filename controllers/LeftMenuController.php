<?php

namespace app\controllers;

use app\components\Settings;
use Yii;
use app\models\Menu;
use app\common\IIPActiveController;
use app\models\MenuSearch;
use app\common\RestResult;
use app\common\Consts;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use mdm\admin\components\Helper;

/**
 * MenuController implements the CRUD actions for Menu model.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class LeftMenuController extends IIPActiveController
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
                'create' => ['post']
            ],
        ];
        return $behaviors;

    }

    /**
     * Lists all Menu models.
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

        $searchModel = new MenuSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
        $pid = isset(Yii::$app->request->getQueryParams()['pid']) ? Yii::$app->request->getQueryParams()['pid'] : null;
        Menu::setPid($pid);
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        $perpage = $dataProvider->getCount();  //在当前页获取数据项的数目
        $count = $dataProvider->getTotalCount();  // 获取所有页面的数据项的总数
        $parentarray = [];
        $clildarray = [];
        $newarray = [];
        foreach ($dataProvider->getModels() as $value) {
            $array['id'] = $value->id;
            $array['name'] = $value->name;
            $array['parent'] = $value->parent;
            $array['route'] = $value->route;
            $array['order'] = $value->order;
            $array['data'] = $value->data;
            $array['fontend_path'] = $value->fontend_path;
            if ($value['parent'] == null) {
                $parentarray[] = $array;
            } else {
                $clildarray[] = $array;
            }
            $newarray = [
                "parent" => $parentarray,
                "chile" => $clildarray
            ];

        }
        $newmenu = [];
        if ($newarray) {
            $newchild = ArrayHelper::index($newarray['chile'], null, 'parent');
            foreach ($newarray['parent'] as $value) {
                if (array_key_exists($value['id'], $newchild)) {
                    $value['children'] = $newchild[$value['id']];
                    $newmenu[] = $value;
                } else {
                    $newmenu[] = $value;

                }
            }
        }
        $result->code = Consts::REST_OK;
        $result->data = [
            "paging" => [
                'page' => $page,
                'per-page' => $perpage,
                'count' => $count
            ],
            "result" => $newmenu
        ];
        return $result;
    }


    /**
     * Creates a new Menu model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $model = new Menu;
        $pid = Yii::$app->request->post()['pid'] ? Yii::$app->request->post()['pid'] : null;
        $model->setPid($pid);
        $model->fontend_path = Yii::$app->request->post()['fontend_path'];
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $result->code = Consts::REST_OK;
            $result->data = [
                "id" => $model->id,
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
     * Updates an existing Menu model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $pid = Yii::$app->request->get()['pid'];
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $model = $this->findModel($id);
        $model->setPid($pid);
        foreach ($model as $k => $v) {
            $data['parent_name'] = $model->menuParent ? $model->menuParent->name : "";
            $data[$k] = $v;
        }
        if ($model->load(Yii::$app->request->post())) {
            $model->fontend_path = Yii::$app->request->post()['fontend_path'];
            $model->save();
            Helper::invalidate();
            $result->code = Consts::REST_OK;
            $result->data = [
                "id" => $model->id,
                "parent_name" => $model->parent_name,
                "order" => $model->order
            ];
            $result->msg = Yii::t('app', 'successfully');
        } else {
            if ($model->hasErrors()) {
                $result->code = Consts::REST_MODEL_ERROR;
                $result->errdata = $model->getJsonFineErrors();
                $result->msg = Yii::t('app', 'failure');
            } else {
                $result->code = Consts::REST_OK;
                $result->data = [
                    "model" => $data,
                ];
                $result->msg = Yii::t('app', 'successfully');
            }

        }
        return $result;
    }

    /**
     * Deletes an existing Menu model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $pid = Yii::$app->request->get()['pid'];
        $model = $this->findModel($id);
        $model->setPid($pid);
        if ($model->getChildMenus()->one()) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => null,
                    'msg' => Yii::t('app', 'Delete failed, submenu exists'),
                ]);
        }
        if ($this->findModel($id)->delete() !== false) {
            Helper::invalidate();
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => [
                        'id' => $id
                    ],
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => null,
                'msg' => Yii::t('app', 'failure'),
            ]);

    }

    /**
     * Finds the Menu model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  integer $id
     * @return Menu the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Menu::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function actionGetMenuSource()
    {
        $pid = Yii::$app->request->get()['pid'];
        $model = new Menu;
        $model->setPid($pid);
        $menus = $model->getMenuSource();
        return $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $menus,
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }


    public function actionGetSavedRoutes($pid)
    {
        $model = new Menu;
        $routes = $model->getLeftSavedRoutes($pid);
        return $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $routes,
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }


}
