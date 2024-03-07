<?php

namespace app\models;

use app\helpers\EasemobHelper;
use Yii;
use yii\behaviors\BlameableBehavior;

/**
 * This is the model class for table "{{%live_room_confr}}".
 *
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $confr_id
 * @property string $confr_name
 * @property string $confr_password
 * @property int $room_id
 * @property int $status
 * @property int $type
 * @property string $confr_admin_role_token
 *
 * @property LiveRoom $room
 */
class LiveRoomConfr extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%live_room_confr}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['room_id'], 'unique'],
            [['created_at', 'updated_at', 'confr_admin_role_token'], 'safe'],
            [['created_by', 'updated_by', 'room_id', 'status', 'type'], 'integer'],
            [['confr_id', 'confr_name', 'confr_password'], 'string', 'max' => 255],
            [['room_id'], 'exist', 'skipOnError' => true, 'targetClass' => LiveRoom::className(), 'targetAttribute' => ['room_id' => 'id']],
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
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'confr_id' => Yii::t('app', 'Confr ID'),
            'confr_name' => Yii::t('app', 'Confr Name'),
            'confr_password' => Yii::t('app', 'Confr Password'),
            'room_id' => Yii::t('app', 'Room ID'),
            'status' => Yii::t('app', 'Status'),
            'type' => Yii::t('app', 'Type'),
            'confr_admin_role_token' => Yii::t('app', 'Role Token')
        ];
    }

    public function behaviors()
    {
        return [
            'blameable' => [
                'class' => BlameableBehavior::class,
            ],
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function ($event) {
                    return date("Y-m-d H:i:s");
                },
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoom()
    {
        return $this->hasOne(LiveRoom::className(), ['id' => 'room_id']);
    }

    public static function createConferenceAndJoin($room_id, $confr_username, $confr_user_token, $type = 10)
    {
        $confr = EasemobHelper::createConferenceAndJoin($confr_username, $confr_user_token, $type);
        if (empty($confr))
            return null;

        /**
         * @var $confr_model LiveRoomConfr
         */
        $confr_model = new LiveRoomConfr(
            [
                'confr_id' => $confr['confrId'],
                'confr_name' => $confr['confrName'],
                'confr_password' => $confr['password'],
                'room_id' => $room_id,
                'status' => 1,
                'type' => $confr['type'],
                'confr_admin_role_token' => $confr['roleToken']
            ]);
        if ($confr_model->save())
            return $confr_model;
        return null;
    }

    public static function createConfrForRoom($room_id)
    {
        // conference
        $confr_admin = EasemobUser::getConfrAdminUser();
        if (empty($confr_admin))
            return false;
        $confr = LiveRoomConfr::createConferenceAndJoin($room_id, $confr_admin->username, $confr_admin->access_token);
        return $confr;
    }

    public static function destroyConference($room_id)
    {
        $confrs = LiveRoomConfr::find()
            ->where(['room_id' => $room_id,
                'status' => 1])
            ->all();
        /**
         * @var $confr LiveRoomConfr
         */
        foreach ($confrs as $confr) {
            $x = EasemobHelper::destroyConference($confr->confr_id, $confr->confr_admin_role_token);
            $confr_users = EasemobUser::find()
                ->leftJoin(EasemobUserOnline::tableName(), EasemobUserOnline::tableName() . '.easemob_user_id = ' . EasemobUser::tableName() . '.id')
                ->where([EasemobUserOnline::tableName() . '.room_id' => $room_id])
                ->all();

            foreach ($confr_users as $confr_user) {
                $x = EasemobUser::unlockUser($confr_user);
            }

            $confr->status = 0;
            return $confr->save(false, ['status', 'updated_at']);
        }
        return true;
    }

    public static function getConference($room_id)
    {
        $confr = LiveRoomConfr::find()
            ->where(['room_id' => $room_id, 'status' => 1])
            ->one();
        return $confr;
    }

    public static function changeStatus($confr_id, $status)
    {
        $confr = LiveRoomConfr::findOne($confr_id);
        if ($confr == null)
            return false;
        $confr->status = $status;
        return $confr->save(false, ['status', 'updated_at']);
    }


}
