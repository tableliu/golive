<?php

namespace app\controllers;

use Yii;
use mdm\admin\models\BizRule;
use app\common\IIPActiveController;
use mdm\admin\models\searchs\BizRule as BizRuleSearch;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use mdm\admin\components\Helper;
use mdm\admin\components\Configs;
use app\common\RestResult;
use app\common\Consts;

/**
 * Description of RuleController
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class RuleController extends IIPActiveController
{
    public $modelClass = 'mdm\admin\models\Route';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'create' => ['post'],
                'update' => ['post'],
                'delete' => ['post'],
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
        $searchModel = new BizRuleSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
        $result->code = Consts::REST_OK;
        $result->data = [
            "result" => array_values($dataProvider->getModels())
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
        $model = $this->findModel($id);

        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => [
                    "model" => $model,
                ],
                'msg' => Yii::t('app', 'successfully'),
            ]);
    }

    /**
     * Creates a new AuthItem model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new BizRule(null);
        $post['BizRule']['name'] = Yii::$app->request->post()['name'];
        $post['BizRule']['className'] = Yii::$app->request->post()['className'];
        if ($model->load($post) && $model->save()) {
            Helper::invalidate();
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => [
                        "id" => $model->name,
                    ],
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        } else {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_MODEL_ERROR,
                    'errdata' => $model->getJsonFineErrors(),
                    'msg' => Yii::t('app', 'successfully'),
                ]);

        }
    }

    /**
     * Updates an existing AuthItem model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param  string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        if (Yii::$app->request->post()) {
            $post['BizRule']['name'] = Yii::$app->request->post()['name'];
            $post['BizRule']['className'] = Yii::$app->request->post()['className'];

            if ($model->load($post) && $model->save()) {
                Helper::invalidate();
                return Yii::createObject(
                    [
                        'class' => RestResult::className(),
                        'code' => Consts::REST_OK,
                        'data' => [
                            "id" => $model->name,
                        ],
                        'msg' => Yii::t('app', 'successfully'),
                    ]);
            }
        } else {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_MODEL_ERROR,
                    'errdata' => $model->getJsonFineErrors(),
                    'msg' => Yii::t('app', 'successfully'),
                ]);

        }
    }

    /**
     * Deletes an existing AuthItem model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param  string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        Configs::authManager()->remove($model->item);
        Helper::invalidate();

        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => [
                    "id" => $id,
                ],
                'msg' => Yii::t('app', 'successfully'),
            ]);
    }

    /**
     * Finds the AuthItem model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param  string $id
     * @return AuthItem      the loaded model
     * @throws HttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $item = Configs::authManager()->getRule($id);
        if ($item) {
            return new BizRule($item);
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
