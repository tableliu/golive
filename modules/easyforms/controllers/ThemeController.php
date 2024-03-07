<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.0
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

namespace app\modules\easyforms\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\modules\easyforms\helpers\ArrayHelper;
use app\modules\easyforms\models\User;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\Theme;
use app\modules\easyforms\models\ThemeSearch;

/**
 * Class ThemeController
 * @package app\controllers
 */
class ThemeController extends Controller
{

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                    'delete-multiple' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'delete-multiple' => [
                'class' => '\app\components\actions\DeleteMultipleAction',
                'modelClass' => 'app\models\Theme',
                'afterDeleteCallback' => function () {
                    Yii::$app->getSession()->setFlash(
                        'success',
                        Yii::t('form', 'The selected items have been successfully deleted.')
                    );
                },
            ],
        ];
    }

    /**
     * Lists all Theme models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ThemeSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Theme model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'themeModel' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Theme model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $themeModel = new Theme();
        $users = User::find()->select(['id', 'username'])->asArray()->all();
        $users = ArrayHelper::map($users, 'id', 'username');

//        if (Yii::$app->user->can('admin')) {
//            // Select id & name of all forms in the system
//            $forms = Form::find()->select(['id', 'name'])->asArray()->all();
//        } else {
//            // Only the user forms
//            $forms = Form::find()->select(['id', 'name'])->where(['created_by' => Yii::$app->user->id])->asArray()->all();
//        }
        $forms = Form::find()->select(['id', 'name'])->asArray()->all();
        if ($themeModel->load(Yii::$app->request->post()) && $themeModel->save()) {
            Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The theme has been successfully created.'));
            return $this->redirect(['view', 'id' => $themeModel->id]);
        } else {
            return $this->render('create', [
                'themeModel' => $themeModel,
                'forms' => $forms,
                'users' => $users,
            ]);
        }
    }

    /**
     * Updates an existing Theme model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $themeModel = $this->findModel($id);
        $users = User::find()->select(['id', 'username'])->asArray()->all();
        $users = ArrayHelper::map($users, 'id', 'username');

        if (Yii::$app->user->can('admin')) {
            // Select id & name of all forms in the system
            $forms = Form::find()->select(['id', 'name'])->asArray()->all();
        } else {
            // Only the user forms
            $forms = Form::find()->select(['id', 'name'])->where(['created_by' => Yii::$app->user->id])->asArray()->all();
        }

        if ($themeModel->load(Yii::$app->request->post()) && $themeModel->save()) {
            Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The theme has been successfully updated.'));
            return $this->redirect(['view', 'id' => $themeModel->id]);
        } else {
            return $this->render('update', [
                'themeModel' => $themeModel,
                'forms' => $forms,
                'users' => $users,
            ]);
        }
    }

    /**
     * Deletes an existing Theme model.
     * If the delete is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->getSession()->setFlash('success', Yii::t('form', 'The theme has been successfully deleted.'));

        return $this->redirect(['index']);
    }

    /**
     * Finds the Theme model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Theme the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($themeModel = Theme::findOne($id)) !== null) {
            return $themeModel;
        } else {
            throw new NotFoundHttpException(Yii::t("app", "The requested page does not exist."));
        }
    }
}
