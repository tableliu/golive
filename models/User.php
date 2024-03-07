<?php

namespace app\models;

use app\components\Settings;
use app\helpers\wxHelper;
use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\FormUser;
use app\modules\easyforms\models\Theme;
use Yii;
use app\common\IIPActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\Query;


/**
 * This is the model class for table "tbl_user".
 *
 * @property string $id
 * @property string $role_id
 * @property integer $status
 * @property string $email
 * @property string $username
 * @property string $password
 * @property string $auth_key
 * @property string $access_token
 * @property string $logged_in_ip
 * @property string $logged_in_at
 * @property string $created_ip
 * @property string $created_at
 * @property string $updated_at
 * @property string $banned_at
 * @property string $banned_reason
 * @property string $last_pid
 * @property string $wx_openid
 * @property string $wx_user_name
 * @property string $wx_unionid
 * @property string $wx_g_openid
 * @property string $last_language
 *
 */
class User extends IIPActiveRecord implements \yii\web\IdentityInterface
{
    public $newPassword;
    public $oldPassword;
    public $retypePassword;
    public $role;

    // public $update_count;

    public function rules()
    {
        return [
            ['username', 'filter', 'filter' => 'trim'],
            ['username', 'required', 'message' => '用户名不可以为空'],
            ['username', 'unique', 'targetClass' => '\app\models\User', 'message' => '用户名已存在!.'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            [['email'], 'filter', 'filter' => 'trim'],
            // ['email', 'required', 'message' => '邮箱不可以为空'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            // ['newPassword', 'required', 'message' => '密码不可以为空', 'on' => ['create']],
            ['newPassword', 'string', 'min' => 6, 'tooShort' => '密码至少填写6位'],
            [['oldPassword'], 'validatePassword', 'on' => ['change-password']],
            [['newPassword'], 'validatePassword', 'on' => ['change-password']],
            [['retypePassword'], 'compare', 'compareAttribute' => 'newPassword'],
            // ['wx_user_name', 'required', 'message' => '微信名称不可以为空'],
            ['logged_in_at', 'filter', 'filter' => 'trim'],
            ['banned_at', 'filter', 'filter' => 'trim'],
            ['wx_user_name', 'filter', 'filter' => 'trim'],
            //            [['status'], 'required', 'on' => ['admin']],
            //            [['status'], 'integer', 'on' => ['admin']],
            [['status'], 'integer'],
            ['update_count', 'integer'],
            // ['role', 'required', 'message' => Yii::t('app', 'The role must not be empty')]
            // ['wx_openid', 'required', 'message' => '微信openid不可以为空'],
            // ['wx_session_key', 'required', 'message' => '微信session_key不可以为空'],


        ];
    }

    public static function tableName()
    {
        return Settings::DsnName() . ".user";
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne($id);
    }


    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $user = User::find()
            ->where(['username' => $username])
            ->asArray()
            ->one();

        if ($user) {
            return new static($user);
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->auth_key === $authKey;
    }

    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::className(), ['user_id' => 'id']);
    }

    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);


    }


    public function updateLoginMeta()
    {
        $User = User::findOne($this->id);
        $User->logged_in_at = gmdate("Y-m-d H:i:s");
        $User->save(false);
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        $token = UserToken::findByToken($token, UserToken::TYPE_REST_LOGIN, true);
        if (isset($token)) {
            return static::findOne(['id' => $token->user_id, 'status' => null]);
        }
        return null;
    }

    public static function findByOpenid($openid)
    {
        return static::findOne(['wx_openid' => $openid]);
    }

    public function GetAssignmentsStr($userId = null)
    {
        $userId = $userId == null ? $this->id : $userId;
        $models = (new Query())->select(['item_name'])
            ->from('auth_assignment')
            ->where(['user_id' => $userId])
            ->all();
        return implode('，', array_column($models, "item_name"));
    }

    public function getMajorRole($user_id = null)
    {
        $user_id = empty($user_id) ? $this->id : $user_id;
        $model = (new Query())
            ->select(['item_name', 'user_id'])
            ->from('auth_assignment')
            ->where(['user_id' => $user_id])
            ->orderBy('order desc')
            ->one();
        return $model['item_name'];
    }

    public static function relieveWxUserCorrelation($token)
    {
        $model = UserToken::findOne(['token' => $token]);
        UserToken::findOne($model['id'])->delete();

        $user_data = User::findOne(['id' => $model['user_id']]);
        $user_data->wx_openid = null;
        $user_data->wx_user_name = null;
        $user_data->wx_unionid = null;
        $user_data->wx_g_openid = null;
        if ($user_data->save())
            return true;

        return false;


    }

    public static function getDb()
    {
        return Yii::$app->settings->getUserDB();
    }

    public function getInfoProfile($user_id)
    {


    }

    // easyform
    public function getForms()
    {
        return $this->hasMany(Form::className(), ['created_by' => 'id'])->inverseOf('author');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThemes()
    {
        return $this->hasMany(Theme::className(), ['created_by' => 'id'])->inverseOf('author');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserForms()
    {

        return self:: hasMany(FormUser::className(), ['user_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssignedForms()
    {
        return $this->hasMany(Form::className(), ['id' => 'user_id'])
            ->via('userForms');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProject()
    {

        return $this->hasOne(Project::className(), ['pid' => 'last_pid']);
    }


    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    public static function updateUserGOpenid($unionId, $user_id)
    {
        $userModel = User::findone($user_id);
        $userModel->wx_g_openid = wxHelper::getServiceAccountUserOpenid($unionId);
        return $userModel->save(false);

    }


    //获取检验员列表
    public static function getInspectorList()
    {
        $model = User::find()
            ->from(User::tableName() . ' A')
            ->select(['A.id', 'A.status', 'C.name as name', 'B.full_name'])
            ->leftJoin(Profile::tableName() . ' B', 'A.id = B.user_id')
            ->leftJoin(InspectionUnit::tableName() . ' C', 'C.id = E.ins_unit_id')
            ->leftJoin('auth_assignment' . ' D', 'D.user_id = A.id')
            ->leftJoin(InspectionUnitUser::tableName() . ' E', 'E.user_id = A.id')
            ->andwhere(['item_name' => Yii::$app->params['Role.Inspector'], 'A.status' => null])
            ->asArray()
            ->all();

        $result = ArrayHelper::index($model, null, 'name');
        $data = [];
        foreach ($result as $key => $val) {
            $array['company'] = $key;
            $array['data'] = $val;
            $data[] = $array;

        }
        return $data;
    }


}
