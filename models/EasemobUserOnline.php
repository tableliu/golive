<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "{{%easemob_user_online}}".
 *
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $wamp_session_id  //一个 wamp_session_id 可能在多个 room 中
 * @property string $easemob_user_username
 * @property int $easemob_user_id
 * @property int $room_id
 *
 * @property EasemobUser $easemobUser
 */
class EasemobUserOnline extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%easemob_user_online}}';
    }

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
            [['room_id', 'wamp_session_id'], 'unique', 'targetAttribute' => ['room_id', 'wamp_session_id']],
            [['created_at', 'updated_at'], 'safe'],
            [['easemob_user_id', 'room_id'], 'integer'],
            [['easemob_user_username'], 'string', 'max' => 255],
            [['easemob_user_id'], 'exist', 'skipOnError' => true, 'targetClass' => EasemobUser::className(), 'targetAttribute' => ['easemob_user_id' => 'id']],
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
            'easemob_user_username' => Yii::t('app', 'Easemob User Username'),
            'easemob_user_id' => Yii::t('app', 'Easemob User ID'),
            'room_id' => Yii::t('app', 'Room ID'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getEasemobUser()
    {
        return $this->hasOne(EasemobUser::className(), ['id' => 'easemob_user_id']);
    }

    public static function getConfrUserForRoom($room_id, $wamp_session_id)
    {
        $confr_user_online = EasemobUserOnline::find()
            ->where([
                'room_id' => $room_id,
                'wamp_session_id' => $wamp_session_id
            ])
            ->one();
        if (!empty($confr_user_online))
            return $confr_user_online->easemobUser;

        $confr_user = EasemobUser::getAvailableUser();
        if (empty($confr_user))
            return null;
        if (EasemobUser::lockUser($confr_user, $wamp_session_id, $room_id))
            return $confr_user;
        return null;
    }
}
