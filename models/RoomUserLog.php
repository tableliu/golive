<?php

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\Query;

/**
 * This is the model class for table "room_user_log".
 *
 * @property int $id
 * @property int $user_id
 * @property int $room_id
 * @property string $action
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 */
class RoomUserLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    const ROOM_USER_ENTER_ACTION = "enter";
    const ROOM_USER_LEAVE_ACTION = "leave";

    public static function tableName()
    {
        return 'room_user_log';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'room_id'], 'required'],
            [['user_id', 'room_id', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['action'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'room_id' => 'Room ID',
            'action' => 'Action',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
            BlameableBehavior::className(),
        ];
    }

    public static function getTotalLiveMeetingLength($user_id)
    {
        $room_count = (new Query())
            ->select(['id'])
            ->from(RoomUserLog::tableName())
            ->where(["user_id" => $user_id, 'action' => RoomUserLog::ROOM_USER_ENTER_ACTION])
            ->groupBy("room_id")
            ->count();
        $RoomUserLog = (new Query())
            ->select(['id', 'action'])
            ->from(RoomUserLog::tableName())
            ->where(["user_id" => $user_id])
            ->orderBy("id DESC")
            ->one();
        $RoomUserLog['action'] == RoomUserLog::ROOM_USER_ENTER_ACTION ?
            $andWhere = ['!=', 'id', $RoomUserLog['id']] :
            $andWhere = [];
        $RoomUserLogEnter = (new Query())
            ->select(['sum(UNIX_TIMESTAMP(`created_at`)) as total_enter_time_stamp'])
            ->from(RoomUserLog::tableName())
            ->where(["user_id" => $user_id, 'action' => RoomUserLog::ROOM_USER_ENTER_ACTION])
            ->andWhere($andWhere)
            ->one();
        $RoomUserLogLeave = (new Query())
            ->select(['sum(UNIX_TIMESTAMP(`created_at`)) as total_leave_time_stamp'])
            ->from(RoomUserLog::tableName())
            ->where(["user_id" => $user_id, 'action' => RoomUserLog::ROOM_USER_LEAVE_ACTION])
            ->one();

        $firstLeaveTime = (new Query())
                              ->select(['(DATE_FORMAT(created_at,"%Y-%m-%d")) as created_time'])
                              ->from(RoomUserLog::tableName())
                              ->where(["user_id" => $user_id, 'action' => RoomUserLog::ROOM_USER_ENTER_ACTION])
                              ->Orderby("id ASC")
                              ->one()['created_time'];
        $days = $firstLeaveTime ? (strtotime(date('Y-m-d')) - strtotime($firstLeaveTime)) / 3600 / 24 : null;
        $cooperation_time = $RoomUserLogLeave['total_leave_time_stamp'] - $RoomUserLogEnter['total_enter_time_stamp'];
        return $array = [
            "cooperation_time" => $cooperation_time ? round($cooperation_time / 60) : null,
            "average_cooperation_time" => $days ? round($cooperation_time / $days / 60) : null,
            "room_count" => $room_count
        ];


    }

    public static function saveRoomUserLog($room_id, $user_id, $action)
    {
        $roomUserLog = new RoomUserLog();
        $roomUserLog->user_id = $user_id;
        $roomUserLog->room_id = $room_id;
        $roomUserLog->action = $action;
        return $roomUserLog->save(false);

    }

    public static function getRoomMeetingLength($room_id, $user_id)
    {

        $RoomUserLogEnter = (new Query())
            ->select(['sum(UNIX_TIMESTAMP(`created_at`)) as total_enter_time_stamp'])
            ->from(RoomUserLog::tableName())
            ->where(["user_id" => $user_id, 'action' => RoomUserLog::ROOM_USER_ENTER_ACTION, "room_id" => $room_id])
            ->one();
        $RoomUserLogLeave = (new Query())
            ->select(['sum(UNIX_TIMESTAMP(`created_at`)) as total_leave_time_stamp'])
            ->from(RoomUserLog::tableName())
            ->where(["user_id" => $user_id, 'action' => RoomUserLog::ROOM_USER_LEAVE_ACTION, "room_id" => $room_id])
            ->one();
        $room_cooperation_time = $RoomUserLogLeave['total_leave_time_stamp'] - $RoomUserLogEnter['total_enter_time_stamp'];
        if ($room_cooperation_time)
            return round($room_cooperation_time / 60);
        return null;

    }


    public static function getUserLeaveRoomTime($room_id, $user_id)
    {
        $room_user_log = RoomUserLog::find()
            ->where(['room_id' => $room_id, 'user_id' => $user_id, 'action' => RoomUserLog::ROOM_USER_LEAVE_ACTION])
            ->orderBy("created_at DESC")
            ->asArray()
            ->one();
        return $room_user_log['created_at'];
    }

}
