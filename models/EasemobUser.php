<?php

namespace app\models;

use app\helpers\EasemobHelper;
use app\modules\rest\common\NotFoundHttpRestException;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\web\ServerErrorHttpException;

/**
 * This is the model class for table "{{%confr_user}}".
 *
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $username
 * @property string $password
 * @property int $status
 * @property string $access_token
 * @property string $token_created_at
 * @property int $token_expires_in
 * @property string $uuid
 * @property string $last_unlock_time
 * @property int $lock
 * @property int $created_by
 * @property int $updated_by
 */
class EasemobUser extends \yii\db\ActiveRecord
{
    const MAX_REGISTER_USER = 100;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%easemob_user}}';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->settings->getUserDB();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'last_unlock_time', 'token_created_at', 'access_token'], 'safe'],
            [['username', 'password', 'status'], 'required'],
            [['status', 'token_expires_in', 'lock', 'created_by', 'updated_by'], 'integer'],
            [['username', 'uuid'], 'string', 'max' => 255],
            [['password'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'username' => Yii::t('app', 'Username'),
            'password' => Yii::t('app', 'Password'),
            'status' => Yii::t('app', 'Status'),
            'token_created_at' => Yii::t('app', 'Token Created At'),
            'access_token' => Yii::t('app', 'Access Token'),
            'token_expires_in' => Yii::t('app', 'Token Expires In'),
            'uuid' => Yii::t('app', 'Uuid'),
            'last_unlock_time' => Yii::t('app', 'Last Unlock Time'),
            'lock' => Yii::t('app', 'Lock'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ],
            BlameableBehavior::class,
        ];
    }

    /**
     * @return EasemobUser|null
     * @throws NotFoundHttpRestException
     * @throws ServerErrorHttpException
     */
    public static function getAvailableUser()
    {
        /**
         * @var $user EasemobUser
         */
        $user = self::find()
            ->where([
                'lock' => 0,
                'status' => 1
            ])
            ->orderBy(['last_unlock_time' => SORT_ASC])//使用最早被释放的
            ->one();

        if (empty($user)) {
            if (self::find()->count() >= self::MAX_REGISTER_USER)
                throw new ServerErrorHttpException('Max user count reached.');
            $user = self::createUser();
        }

        if (!empty($user)) {
            if (empty($user->token_created_at) ||
                (strtotime($user->token_created_at) + $user->token_expires_in + 60) < time()) {
                $token_data = EasemobHelper::fetchUserToken($user->username, $user->password);
                if (empty($token_data))
                    throw new NotFoundHttpRestException('fetch user failed.');
                $user->access_token = $token_data['access_token'];
                $user->token_expires_in = $token_data['expires_in'];
                $user->token_created_at = date("Y-m-d H:i:s");
                if (!$user->save())
                    throw new ServerErrorHttpException('Update user failed.');
            }

            return $user;
        }


        return;
    }

    public static function createUser()
    {
        $new_user = EasemobHelper::registerUser();
        /**
         * @var $user EasemobUser
         */
        $user = new EasemobUser([
            'username' => $new_user['username'],
            'password' => $new_user['password'],
            'uuid' => $new_user['uuid'],
            'status' => 1,
            'lock' => 0
        ]);
        if ($user->save())
            return $user;
        return null;
    }

    /**
     * @param EasemobUser $user
     * @param $online_id
     * @param $room_id
     * @return mixed
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\StaleObjectException
     */
    public static function lockUser($user, $wamp_session_id, $room_id)
    {
        $user->lock = 1;
        if ($user->update(false, ['lock']) !== false) {
            /**
             * @var EasemobUserOnline $user_online
             */
            $user_online = new EasemobUserOnline([
                'easemob_user_username' => $user->username,
                'easemob_user_id' => $user->id,
                'wamp_session_id' => $wamp_session_id,
                'room_id' => $room_id
            ]);
            return $user_online->save();
        }
        return false;
    }

    /**
     * @param EasemobUser $user
     * @return mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function unlockUser($user)
    {
        EasemobUserOnline::deleteAll(['easemob_user_id' => $user->id]);

        $user->lock = 0;
        $user->last_unlock_time = date('Y-m-d H:i:s', time());
        return $user->update(false, ['lock', 'last_unlock_time']);
    }

    public static function getConfrAdminUser()
    {
        /**
         * @var $user EasemobUser
         */
        $user = self::find()
            ->where([
                'username' => Yii::$app->params['LiveStreaming.ConfrAdminUserName']
            ])
            ->one();
        if (!empty($user)) {
            if (empty($user->token_created_at) ||
                (strtotime($user->token_created_at) + $user->token_expires_in + 60) < time()) {
                $token_data = EasemobHelper::fetchUserToken($user->username, $user->password);
                if (empty($token_data))
                    throw new NotFoundHttpRestException('fetch user failed.');
                $user->access_token = $token_data['access_token'];
                $user->token_expires_in = $token_data['expires_in'];
                $user->token_created_at = date("Y-m-d H:i:s");
                if (!$user->save())
                    throw new ServerErrorHttpException('Update user failed.');
            }

            return $user;
        }
        throw new ServerErrorHttpException('No admin user.');

    }
}
