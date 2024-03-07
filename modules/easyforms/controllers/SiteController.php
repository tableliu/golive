<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && !$model->login()) {
            return $this->goBack();
        }

        // $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionSite()
    {

        $this->layout = 'admin'; // In @app/views/layouts
        if (Yii::$app->request->post()) {
            Yii::$app->settings->set('app.name', Yii::$app->request->post('app_name', Yii::$app->settings->get('app.name')));
            Yii::$app->settings->set('app.description', Yii::$app->request->post('app_description', Yii::$app->settings->get('app.description')));
            Yii::$app->settings->set('app.adminEmail', Yii::$app->request->post('app_adminEmail', Yii::$app->settings->get('app.adminEmail')));
            Yii::$app->settings->set('app.supportEmail', Yii::$app->request->post('app_supportEmail', Yii::$app->settings->get('app.supportEmail')));
            Yii::$app->settings->set('app.noreplyEmail', Yii::$app->request->post('app_noreplyEmail', Yii::$app->settings->get('app.noreplyEmail')));
            Yii::$app->settings->set('app.reCaptchaSiteKey', Yii::$app->request->post('app_reCaptchaSiteKey', Yii::$app->settings->get('app.reCaptchaSiteKey')));
            Yii::$app->settings->set('app.reCaptchaSecret', Yii::$app->request->post('app_reCaptchaSecret', Yii::$app->settings->get('app.reCaptchaSecret')));

            // Membership
            $anyoneCanRegister = Yii::$app->request->post('app_anyoneCanRegister', null);
            $useCaptcha = Yii::$app->request->post('app_useCaptcha', null);
            $loginWithoutPassword = Yii::$app->request->post('app_loginWithoutPassword', null);
            Yii::$app->settings->set('app.anyoneCanRegister', is_null($anyoneCanRegister) ? 0 : 1);
            Yii::$app->settings->set('app.useCaptcha', is_null($useCaptcha) ? 0 : 1);
            Yii::$app->settings->set('app.loginWithoutPassword', is_null($loginWithoutPassword) ? 0 : 1);
            Yii::$app->settings->set('app.defaultUserRole', Yii::$app->request->post('app_defaultUserRole', Yii::$app->settings->get('app.defaultUserRole')));

            // Logo
            $image = UploadedFile::getInstanceByName('logo');
            if ($image) {
                $logoDir = 'static_files/uploads/logos';
                $oldImage = Yii::$app->settings->get('app.logo');
                $newImage = $logoDir . '/' . $image->baseName . '.' . $image->extension;
                if (FileHelper::createDirectory($logoDir)) {
                    if ($image->saveAs($newImage)) {
                        @unlink($oldImage);
                        Yii::$app->settings->set('app.logo', $newImage);
                    }
                }
            }

            // Show success alert
            Yii::$app->getSession()->setFlash(
                'success',
                Yii::t('form', 'The site settings have been successfully updated.')
            );
        }

        return $this->render('site');
    }


}
