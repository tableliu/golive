<?php

namespace app\modules\onsite\controllers;


use app\common\Consts;
use app\common\RestResult;
use app\components\rest\BaseAPIController;
use app\components\rest\RestHttpBearerAuth;
use app\models\Project;
use app\models\ProjectUser;
use app\models\User;
use app\models\UserSetting;
use app\models\UserToken;
use app\modules\onsite\models\LoginForm;
use Yii;
use yii\filters\VerbFilter;

/**
 * VideoJobController implements the CRUD actions for VideoJob model.
 */
class UserController extends BaseAPIController
{
    const ERRCODE_LOGIN_FAILED_USERNAME_PASSWORD_ERR = 1;
    const ERRCODE_LOGIN_FAILED_OTHER_ERR = 2;

    const ERRCODE_CHANGE_PROJECT_PROJECT_NOT_EXIST = 1;
    const ERRCODE_CHANGE_PROJECT_FAILED = 2;

    const ERRCODE_LOGOUT_FAILED = 1;

    /**
     * @inheritdoc
     */

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'login' => ['post'],
                'logout' => ['post'],
            ],
        ];
        $behaviors['authenticator'] = [
            'class' => RestHttpBearerAuth::className(),
            'except' => ['login', 'get-self-setting']
        ];

        $behaviors['access'] = [
            'class' => 'app\components\filters\RestAccessControl',
            'allowActions' => [
                'login',
                'logout',
                'change',
                'get-self-setting'
            ]
        ];
        return $behaviors;
    }


    public function actionLogin()
    {
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {
                $token = $this->performLogin(Yii::$app->user->identity->id);
                if (!empty($token))
                    return Yii::createObject(
                        [
                            'class' => RestResult::className(),
                            'code' => Consts::REST_OK,
                            'data' => [
                                'user_id' => Yii::$app->user->identity->id,
                                'user_name' => $model->username,
                                'full_name' => Yii::$app->user->identity->profile->full_name,
                                'avatar_url' => Yii::$app->user->identity->profile->getAvatarUrl(),
                                'token' => $token,
                                'project_id' => Yii::$app->user->identity->last_pid,
                                'project_name' => Yii::$app->user->identity->project->projectName
                            ],
                            'msg' => Yii::t('app', 'Login successfully'),
                        ]);
            } elseif ($model->hasErrors()) {
                return Yii::createObject(
                    [
                        'class' => RestResult::className(),
                        'code' => Consts::REST_DATA_ERROR,
                        'errcode' => $this::ERRCODE_LOGIN_FAILED_USERNAME_PASSWORD_ERR,
                        'msg' => Yii::t('app', 'Incorrect username or password.'),
                    ]);
            }
        }

        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_DATA_ERROR,
                'errcode' => $this::ERRCODE_LOGIN_FAILED_OTHER_ERR,
                'msg' => Yii::t('app', 'Error occurred.'),
            ]);

    }

    protected function performLogin($id)
    {
        $token = '';
        $userToken = new UserToken;
        $restLoginDuration = Yii::$app->params['LoginDuration'];
        $models = User::findOne($id);
        if ($models && !$models->banned_at && Yii::$app->user->login($models, $restLoginDuration)) {
            $expireTime = $restLoginDuration ? date("Y-m-d H:i:s", time() + $restLoginDuration) : null;
            $userToken = $userToken::generate($models->id, $userToken::TYPE_REST_LOGIN, null, $expireTime, "");
            if ($userToken) {
                $token = $userToken->token;
            }

        }
        return $token;

    }

    public function actionProjectIndex()
    {
        $user_id = Yii::$app->user->id;
        $array = Project::search($user_id);
        return $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $array,
                'msg' => Yii::t('app', 'successfully'),
            ]);
    }

    public function actionChangeProject()
    {
        if (!ProjectUser::getProject(Yii::$app->request->post()['project_id'], Yii::$app->user->id)) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_CHANGE_PROJECT_PROJECT_NOT_EXIST,
                    'msg' => Yii::t('app', 'Project not exist.'),
                ]);
        }
        $id = Yii::$app->user->id;
        $model = User::findOne($id);
        $model->last_pid = Yii::$app->request->post()['project_id'];
        if ($model->save())
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        else
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_CHANGE_PROJECT_FAILED,
                    'msg' => Yii::t('app', 'Error occurred.'),
                ]);

    }

    public function actionLogout()
    {
        $id = Yii::$app->user->id;
        $model = UserToken::findOne(['user_id' => $id]);
        if ($model->delete() !== false) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'msg' => Yii::t('app', 'successfully'),
                ]);
        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_DATA_ERROR,
                'errcode' => $this::ERRCODE_LOGOUT_FAILED,
                'msg' => Yii::t('app', 'Error occurred.'),
            ]);
    }

    public function actionGetSelfSetting()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                // 'data' => Yii::$app->params['Mobile.User.Setting'],
                'data' => [
                    "login_require" => 1,
                    "force_clean_user_data" => 0,
                    "props" => [
                        "onsite_job_resovling" => 2,
                        "onsite_job_fps" => 2,
                        "remote_job_resolving" => 2
                    ],
                    "configs" => [
                        "onsite_job_resovling" => [
                            [
                                "name" => "高",
                                "value" => 3
                            ], [
                                "name" => "中",
                                "value" => 2
                            ], [
                                "name" => "低",
                                "value" => 1
                            ]
                        ],
                        "onsite_job_fps" => [
                            [
                                "name" => "高",
                                "value" => 3
                            ], [
                                "name" => "中",
                                "value" => 2
                            ], [
                                "name" => "低",
                                "value" => 1
                            ]
                        ],
                        "remote_job_resolving" => [
                            [
                                "name" => "高",
                                "value" => 3
                            ], [
                                "name" => "中",
                                "value" => 2
                            ], [
                                "name" => "低",
                                "value" => 1
                            ]
                        ]
                    ]

                ]
            ]);
        $userId = Yii::$app->user->id;
        if (empty($userId))
            return $result;
        $userSetting = UserSetting::findOne(["user_id" => $userId, "type" => UserSetting::USER_MOBILE_TYPE_SETTING]);
        if (!empty($userSetting->setting))
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => $userSetting->setting,
                ]);
        return $result;

    }
}
