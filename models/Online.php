<?php

namespace app\models;

use app\common\Consts;
use app\components\Settings;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%online}}".
 *
 * @property int $id
 * @property int $user_id
 * @property string $session_id
 * @property string $wamp_session_id
 * @property string $client_type
 * @property string $created_at
 * @property string $updated_at
 *
 * @property User $user
 */
class Online extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%online}}';
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
            [['created_at', 'updated_at'], 'safe'],
            [['user_id', 'wamp_session_id', 'client_type'], 'required'],
            [['user_id'], 'integer'],
            [['session_id', 'wamp_session_id'], 'string', 'max' => 255],
            [['client_type'], 'string', 'max' => 32],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
            [['wamp_session_id'], 'unique'],
        ];
    }

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function($event) {
                    return date("Y-m-d H:i:s");
                },
            ],
            'liveRoomUserBehavior' => [
                'class' => 'app\components\behaviors\LiveRoomUserBehavior',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'session_id' => Yii::t('app', 'Session ID'),
            'wamp_session_id' => Yii::t('app', 'Wamp Session ID'),
            'client_type' => Yii::t('app', 'Client Type'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['user_id' => 'user_id']);
    }

    public function getLocation()
    {
        return $this->hasMany(UserLocation::class, ['user_id' => 'user_id'])
            ->orderBy("id DESC")
            ->one();
    }

    public static function getOnlineUsers($client_type = null, $is_include_self = true)
    {
        $userModel = User::findOne(Yii::$app->user->id);
        $onlineSql = Online::find()
            ->joinWith(['profile'])
            ->joinWith(['user'])
            ->where(['last_pid' => $userModel['last_pid']])
            ->groupBy("user_id")
            ->orderBy([Online::tableName() . '.id' => SORT_DESC]);
        if ($client_type)
            $onlineSql->andwhere(['client_type' => $client_type]);
        if (!$is_include_self)
            $onlineSql->andwhere(['!=', Online::tableName() . '.user_id', Yii::$app->user->id]);
        return $onlineSql->all();
    }

    public static function pushUserOnlineOffline($is_online, $client_type)
    {
        $web_user_models = Online::getOnlineUsers(Consts::LIVE_USER_TYPE_WEB);
        $web_user_ids = ArrayHelper::getColumn($web_user_models, 'user_id');
        $mobile_user_models = Online::getOnlineUsers(Consts::LIVE_USER_TYPE_MOBILE);
        $mobile_user_ids = ArrayHelper::getColumn($mobile_user_models, 'user_id');
        (new \app\controllers\LivePushController)->pushWebOnlineOffline(Yii::$app->user->identity, $is_online, $client_type, $web_user_ids);
        (new \app\modules\onsite\controllers\LivePushController)->pushMobileOnlineOffline(Yii::$app->user->identity, $is_online, $client_type, $mobile_user_ids);

    }

    public static function isOnline($user_id)
    {
        if (Online::findOne(['user_id' => $user_id]))
            return true;
        return false;
    }


}
