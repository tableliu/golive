<?php

namespace app\controllers;

use app\common\exception\UnauthorizedRestException;
use app\components\Settings;
use app\helpers\CosHelper;
use app\models\ChangePassword;
use app\models\Project;
use app\models\ProjectSearch;
use app\models\ProjectUser;
use app\models\UserToken;
use Yii;
use yii\base\Exception;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\UserSearch;
use app\common\IIPActiveController;
use app\common\RestResult;
use app\common\Consts;
use app\models\User;
use app\models\Profile;
use yii\helpers\ArrayHelper;


/**
 * Class UserController
 * @package app\modules\rest\controllers
 */
class UserController extends IIPActiveController
{

    public $avatarsDir = "upload/avatar/";
    public $modelClass = 'app\models\LoginForm';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'login' => ['POST'],
                'delete' => ['post'],
                'update' => ['post'],
                'logout' => ['post'],

            ],
        ];
        return $behaviors;
    }


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
     * @OAS\Post(
     *   path="/v1/user/login",
     *   tags={"user"},
     *   summary="用户登录",
     *   operationId="login",
     *   description="使用用户名和密码登录系统。",
     *   @OAS\Response(
     *      response="200",
     *      description="请求成功",
     *         @OAS\MediaType(
     *             mediaType="application/json",
     *             @OAS\Schema(
     *                 @OAS\Items(
     *                    ref="#/components/schemas/RestResult"
     *                 )
     *             )
     *         ),
     *   ),
     *   @OAS\RequestBody(
     *         description="Input data format",
     *         @OAS\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OAS\Schema(
     *                 ref="#/components/schemas/RestLogin"
     *             )
     *         )
     *   )
     * )
     */
    public function actionLogin()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $model = new LoginForm();

        if ($model->getStatus(Yii::$app->request->post()['username'])) {
            return $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_MODEL_ERROR,
                    'data' => "",
                    'msg' => Yii::t('app', 'Logon failure the user is disabled'),
                ]);
        }

        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            $result->code = Consts::REST_OK;
            $result->data = [
                'user_id' => Yii::$app->user->identity->id,
                'staff_name' => $model->username,
                'avatar_url' => Yii::$app->user->identity->profile->getAvatarUrl(),
            ];
            $result->msg = Yii::t('app', 'Login successfully');
            return $result;
        } else {
            if ($model->hasErrors()) {
                $result->code = Consts::REST_MODEL_ERROR;
                $result->errdata = $model->getJsonFineErrors();
                $result->msg = Yii::t('app', 'Incorrect username or password.');
            }
            return $result;

        }
    }

    //user_list
    public function actionIndex()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $searchModel = new userSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());
        $newData = [];
        foreach ($dataProvider->getModels() as $key => $val) {
            $data = $val->profile;
            $newData[$key]['id'] = $val->id;
            $newData[$key]['username'] = $val->username;
            $newData[$key]['full_name'] = $data['full_name'];
            $newData[$key]['gender'] = $data['gender'];
            $newData[$key]['telephone'] = $data['telephone'];
            $newData[$key]['user_status'] = $val->status;
            $ProjectSearch = new ProjectSearch();
            foreach ($ProjectSearch->search($val->id) as $project) {
                $project['role'] = Project::getProjectRole($val->id, $project['pid']);
                $newData[$key]['project_data'][] = $project;
            }
        }
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        $perpage = $dataProvider->getCount();
        $count = $dataProvider->getTotalCount();
        if (array_key_exists("sort", Yii::$app->request->getQueryParams())) {
            strpos(Yii::$app->request->getQueryParams()['sort'], '-') !== false ? $sort = substr(Yii::$app->request->getQueryParams()['sort'] . "|SORT_DESC", 1) : $sort = Yii::$app->request->getQueryParams()['sort'] . "|ASC";
        } else {
            $sort = "id|SORT_DESC";
        }
        $result->code = Consts::REST_OK;
        $result->data = [
            "paging" => [
                'page' => $page,
                'per-page' => $perpage,
                'count' => $count
            ],
            "result" => $newData,
            "sort" => $sort,

        ];
        $result->msg = Yii::t('app', 'successfully');
        return $result;

    }

    //user_add
    public function actionCreate()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);

        $modelUser = new User();
        $modelProfile = new profile();
        $post = Yii::$app->request->post();
        array_key_exists('new_password', Yii::$app->request->post()) !== false ? $post['newPassword'] = Yii::$app->request->post()['new_password'] : $post['newPassword'] = "111111";
        if ($modelUser->load($post) && $modelUser->validate() && $modelProfile->load($post) && $modelProfile->validate()) {
            $transaction = Settings::getUserDB()->beginTransaction();
            try {
                $modelUser->setPassword($modelUser->newPassword);
                $modelUser->save();
                $modelProfile->user_id = $modelUser->id;
                $modelProfile->save();
                $dbs = [];
                $project_data = Yii::$app->request->post()['project_data'];
                foreach ($project_data as $project) {
                    foreach ($project['role'] as $role) {
                        Yii::$app->authManager->assignRoles(
                            $project['pid'],
                            $role,
                            $modelUser->id,
                            array_key_exists($role, Yii::$app->params['Role.orders']) ? Yii::$app->params['Role.orders'][$role] : null
                        );
                    }
                    $dbs[] = $project['pid'];
                }
                Project::setUserProject($dbs, $modelUser->id);
                if ($modelUser->getErrors() || $modelProfile->getErrors()) {
                    $result->code = Consts::REST_MODEL_ERROR;
                    $result->errdata = "";
                    $result->msg = Yii::t('app', 'failure');
                    $transaction->rollBack();
                } else {
                    $transaction->commit();
                    $result->code = Consts::REST_OK;
                    $result->data = $modelUser->id;
                }
                return $result;
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } else {
            if ($modelUser->hasErrors()) {
                $result->code = Consts::REST_MODEL_ERROR;
                $result->errdata = $modelUser->getJsonFineErrors();
                $result->msg = Yii::t('app', 'failure');
            }
            return $result;

        }
    }


    //user_edit
    public function actionUpdate($id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);

        $modelUser = $this->findModel_user($id);
        $searchModel = new ProjectSearch();
        $modelProfile = $this->findModel_profile($id);
        $post = Yii::$app->request->post();
        if (!empty($post['new_password'])) {
            $modelUser->setPassword($post['new_password']);
        }
        if ($post) {
            if ($modelUser['username'] == $post['username']) {
                $code = 1;
            } else {
                if ($modelUser['update_count']) {
                    unset($post['username']);
                    $code = -1;
                } else {
                    $post['update_count'] = 1;
                    $code = 1;
                }
            }
        }
        $transaction = Settings::getUserDB()->beginTransaction();
        if ($modelUser->load($post) && $modelUser->validate() && $modelProfile->load($post) && $modelProfile->validate()) {
            try {
                $modelUser->save();
                $modelProfile->save();
                $project_data = Yii::$app->request->post()['project_data'];
                $dbs = [];
                foreach ($project_data as $project) {
                    Yii::$app->authManager->deleteAssignRoles($project['pid'], $modelUser->id);
                    foreach ($project['role'] as $role) {
                        Yii::$app->authManager->assignRoles(
                            $project['pid'],
                            $role,
                            $modelUser->id,
                            array_key_exists($role, Yii::$app->params['Role.orders']) ? Yii::$app->params['Role.orders'][$role] : null
                        );
                    }
                    $dbs[] = $project['pid'];

                }
                ProjectUser::deleteAll(["user_id" => $id]);
                Project::SetUserproject($dbs, $id);
                if ($modelUser->getErrors() || $modelUser->getErrors()) {
                    $transaction->rollBack();
                    $result->code = Consts::REST_MODEL_ERROR;
                } else {
                    $result->code = Consts::REST_OK;
                    $result->data = [
                        'id' => $modelUser->id,
                        'code' => $code
                    ];
                    $result->msg = Yii::t('app', 'successfully');
                    $transaction->commit();
                }
                return $result;

            } catch
            (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        } else {
            $project_date = [];
            $modelUser = $this->findModel_user($id)->getAttributes();

            foreach ($searchModel->search($id) as $project) {
                $project['role'] = explode(",", Project::getProjectRole($id, $project['pid']));
                $project_data[] = $project;
            }
            $modelUser['project_data'] = $project_data;
            $result->code = Consts::REST_OK;
            $result->data = [
                'model_user' => $modelUser,
                'model_profile' => $modelProfile
            ];
            $result->msg = Yii::t('app', 'successfully');
            return $result;
        }
    }

    //user_delete
    public
    function actionDelete($id)
    {
        $transaction = Settings::getUserDB()->beginTransaction();
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);

        try {
            $searchModel = new ProjectSearch();
            $pids = $searchModel->search($id);
            UserToken::deleteAll(['user_id' => $id]);
            $ProjectResult = ProjectUser::deleteAll(['user_id' => $id]);
            $ProfileResult = Profile::deleteAll(['user_id' => $id]);
            $UserResult = User::deleteAll(['id' => $id]);
            foreach ($pids as $v) {
                Yii::$app->authManager->deleteAssignRoles($v['pid'], $id);
            }

            if ($ProfileResult == 0 || $UserResult == 0 || $ProjectResult == 0) {
                $transaction->rollback();
                $result->msg = Yii::t('app', 'Deletion failed');
            } else {
                $transaction->commit();
                $result->code = Consts::REST_OK;
                $result->msg = Yii::t('app', '');
            }
        } catch (\Exception $e) {
            $transaction->rollback();
            $result->code = Consts::REST_SERVER_ERROR;
            $result->msg = Yii::t('app', 'Deletion failed');

        }
        return $result;
    }

    protected
    function findModel_user($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        } else {
            $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => "",
                    'msg' => Yii::t('app', 'The requested page does not exist.')
                ]);
            return $result;
        }
    }

    protected
    function findModel_profile($id)
    {
        if (($model = profile::find()->where(['user_id' => $id])->one()) !== null) {
            return $model;
        } else {
            $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_SERVER_ERROR,
                    'data' => "",
                    'msg' => Yii::t('app', 'The requested page does not exist.')
                ]);
            return $result;
        }
    }


    public
    function actionTry()
    {
        if (YII_ENV_DEV) {
            $cookie = Yii::createObject([
                'class' => 'yii\web\Cookie',
                'name' => "XDEBUG_SESSION",
                'value' => "PHPSTORM"
            ]);
            Yii::$app->getResponse()->getCookies()->add($cookie);
        }

        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => "",
                'msg' => Yii::t('app', 'successfully')
            ]);
        return $result;
    }

    public
    function actionTrySession()
    {
        if (Yii::$app->user->getId() == null)
            throw  new UnauthorizedRestException();
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => "",
                'msg' => Yii::t('app', 'successfully')
            ]);
    }

    public
    function actionLogout()
    {

        Yii::$app->user->logout();

        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => "",
                'msg' => Yii::t('app', 'successfully')
            ]);

        // clear session
        $session = Yii::$app->getSession();
        if (Yii::$app->user->enableSession &&
            isset($session)) {
            $session->destroy();
        }

        return $result;
    }

    public
    function actionChangePassword()
    {
        $model = new ChangePassword();
        if ($model->load(Yii::$app->getRequest()->post()) && $model->change()) {
            return Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_OK,
                    'data' => "",
                    'msg' => Yii::t('app', 'successfully')
                ]);

        } else {
            if ($model->hasErrors()) {
                return Yii::createObject(
                    [
                        'class' => RestResult::className(),
                        'code' => Consts::REST_MODEL_ERROR,
                        'errdata' => $model->getJsonFineErrors(),
                        'msg' => $model->getJsonFineErrors()
                    ]);

            }
        }


    }

    public
    function actionChangeAvatar()
    {
        $userId = Yii::$app->getRequest()->post()['id'];
        $model = Profile::find()->where(['user_id' => $userId])->one();
        if (!empty($model->avatar)) {
            $avatar = array_slice(explode("/", $model->avatar), -2);
            $key = implode("/", $avatar);
            CosHelper::deleteCosObject($key, 'user_avatar');
        }
        $model->avatar = Yii::$app->getRequest()->post()['images'];
        $model->save(false);
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => [
                    "id" => $userId,
                    "avatar" => $model->avatar
                ],
                'msg' => Yii::t('app', 'successfully')
            ]);
    }

    //用户是否禁用

    public
    function actionUserStatus()
    {
        $id = Yii::$app->request->post()['id'];
        $type = Yii::$app->request->post()['type'];
        $type == 1 ? $user_status = null : $user_status = 1;
        $model = User::find()->where(['id' => $id])->one();
        $model->status = $user_status;
        $model->save();
        return $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => [
                    "id" => $id,
                ],
                'msg' => Yii::t('app', 'User status changed successfully')
            ]);


    }


    //个人设置

    public
    function actionPersonalSetting($id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
                'data' => "",
                'msg' => "",
            ]);
        $model_user = $this->findModel_user($id);
        $model_profile = $this->findModel_profile($id);
        $post = Yii::$app->request->post();
        if ($post) {
            if ($model_user['username'] == $post['username']) {
                $code = 1;
            } else {
                if ($model_user['update_count']) {
                    unset($post['username']);
                    $code = -1;
                } else {
                    $post['update_count'] = 1;
                    $code = 1;
                }
            }
        }
        if ($model_user->load($post) && $model_user->validate()) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model_user->save()) {
                    if ($model_profile->load($post) && $model_profile->validate()) {
                        $model_profile->user_id = $model_user->id;
                    }
                    if ($model_profile->save()) {
                        $result->code = Consts::REST_OK;
                        $result->data = [
                            'id' => $model_user->id,
                            'code' => $code
                        ];
                        $result->msg = Yii::t('app', 'successfully');
                    } else {
                        if ($model_profile->hasErrors()) {
                            $result->code = Consts::REST_MODEL_ERROR;
                            $result->errdata = $model_profile->getJsonFineErrors();
                            $result->msg = Yii::t('app', 'failure');
                        }
                    }
                }
                $transaction->commit();
                return $result;
            } catch (Exception $e) {
                $transaction->rollBack();
                throw $e;
            }

        } else {
            if ($model_user->hasErrors()) {
                $result->code = Consts::REST_MODEL_ERROR;
                $result->errdata = $model_user->getJsonFineErrors();
                $result->msg = Yii::t('app', 'failure');
                return $result;
            }

        }
        return $result;

    }


    public function actionView($id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_SERVER_ERROR,
            ]);
        $models = User::find()
            ->from(User::tableName() . ' A')
            ->select(['A.id', 'A.username', 'A.email', 'A.wx_user_name', 'B.full_name', 'B.telephone', 'B.full_name', 'B.avatar', 'B.avatar_url', 'B.remark', 'B.telephone'])
            ->leftJoin(Profile::tableName() . ' B', 'A.id = B.user_id')
            ->Where(['A.id' => $id])
            ->asArray()
            ->one();
        if ($models) {
            if ($models['avatar']) {
                $models['avatar'] = $models['avatar'];
            } else {
                $models['avatar'] = $models['avatar_url'];
            }
            $result->code = Consts::REST_OK;
            $result->data = $models;
            $result->msg = Yii::t('app', 'successfully');
        } else {
            $result->code = Consts::REST_SERVER_ERROR;
            $result->msg = Yii::t('app', 'The requested page does not exist.');
        }
        return $result;

    }


    public
    function actionGetRoleList($pid)
    {

        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => Yii::$app->authManager->getPermissionsRole($pid),
                'msg' => Yii::t('app', 'successfully')
            ]);

    }


    public function actionGetLanguageList()
    {

        $newArray = [];
        foreach (Yii::$app->params['Languages'] as $key => $language) {

            $language['lang'] == Settings::getCurrentLanguage() ? $array[$key]['select'] = true : $array[$key]['select'] = false;
            $array[$key]['language']= $language['lang'];
            $newArray = $array;

        }
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => array_values($newArray),
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }

    public function actionChangeLanguage($language)
    {
        Settings::setUserLastLanguage($language);
        return Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => '',
                'msg' => Yii::t('app', 'successfully'),
            ]);

    }


}
