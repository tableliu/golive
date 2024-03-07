<?php

namespace app\models;

use app\common\Consts;
use app\components\Settings;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%live_room_user}}".
 *
 * @property int $id
 * @property string $updated_at
 * @property string $created_at
 * @property int $room_id
 * @property int $user_id
 * @property string $user_session_id
 * @property string $user_wamp_session_id
 * @property string $user_client_type
 * @property int $status
 * @property LiveRoom $room
 */
class LiveRoomUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%live_room_user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => 1],
            [['updated_at', 'created_at'], 'safe'],
            [['room_id', 'user_id', 'user_wamp_session_id', 'user_client_type'], 'required'],
            [['room_id', 'user_id', 'status'], 'integer'],
            [['user_session_id', 'user_wamp_session_id'], 'string', 'max' => 255],
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
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_at' => Yii::t('app', 'Created At'),
            'room_id' => Yii::t('app', 'Room ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'user_session_id' => Yii::t('app', 'User Session ID'),
            'user_wamp_session_id' => Yii::t('app', 'User Wamp Session ID'),
            'user_client_type' => Yii::t('app', 'user Client Type'),
            'status' => Yii::t('app', 'Status'),
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

            ]

        ];

    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoom()
    {
        return $this->hasOne(LiveRoom::className(), ['id' => 'room_id']);
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['user_id' => 'user_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public static function changeStatus($status, $room_id, $user_id)
    {
        $room_user = LiveRoomUser::findOne(["user_id" => $user_id, "room_id" => $room_id]);
        if (!isset($room_user) || $status == $room_user->status)
            return false;
        $room_user->status = $status;
        return $room_user->save(false);
    }

    public static function getRoomUserStatistics($user_id, $params)
    {

        $query = (new Query())
            ->select(['room.id as id', "count(room_id) as count ", 'room.streamer_id', 'room_user.user_id', 'full_name', 'username', 'room_user.created_at'])
            ->from(LiveRoom::tableName() . ' room')
            ->innerJoin(LiveRoomUser::tableName() . ' room_user', 'room_user.room_id = room.id')
            ->leftJoin(Profile::tableName() . ' profile', 'profile.user_id = room.streamer_id')
            ->leftJoin(User::tableName() . ' user', 'profile.user_id = user.id')
            ->where(["room_user.user_id" => $user_id])
            ->groupBy('room.id')
            ->orderBy('room_user.updated_at DESC');
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => (isset($params['iipsearch']) && !empty($params['iipsearch'])) ? '' : (isset($params['per-page']) ? $params['per-page'] : Yii::$app->params['GridView.pagination.pageSize']),
            ],
        ]);
        return $dataProvider;


    }

    public static function getRoomOnlineUser()
    {
        $liveRoomUsers = (new Query())
            ->select(['username', 'user.id as id', "full_name", 'avatar', 'room_id', 'user_client_type', 'username'])
            ->from(Settings::DsnName() . ".online")
            ->leftJoin(LiveRoomUser::tableName() . ' room_user', 'online.user_id = room_user.user_id')
            ->leftJoin(Profile::tableName() . ' profile', 'profile.user_id = room_user.user_id')
            ->leftJoin(User::tableName() . ' user', 'profile.user_id = user.id')
            ->where(["room_user.status" => 1,])
            ->all();
        $newData = [];
        foreach ($liveRoomUsers as $liveRoomUser) {
            $userLocation = UserLocation::getLastOnlineLocation($liveRoomUser['id']);
            $liveRoomUser["long"] = $userLocation['longitude'];
            $liveRoomUser["lat"] = $userLocation['latitude'];
            $newData[] = $liveRoomUser;

        }
        $newLiveRoomUsers = [];
        foreach (ArrayHelper::index($newData, null, 'room_id') as $key => $liveRoomUser) {
            $roomUsers = ArrayHelper::index($liveRoomUser, null, 'user_client_type');
            $data = [
                "room_id" => $key,
                "name" => LiveRoom::findOne(['id' => $key])['name'],
                'peoples' => isset($roomUsers[Consts::LIVE_USER_TYPE_WEB]) ? $roomUsers[Consts::LIVE_USER_TYPE_WEB] : [],
                "streamer" => isset($roomUsers[Consts::LIVE_USER_TYPE_MOBILE][0]) ? $roomUsers[Consts::LIVE_USER_TYPE_MOBILE][0] : null,
            ];
            $newLiveRoomUsers[] = $data;
        }
        return $newLiveRoomUsers;

    }

    public static function isOnlyStreamer($room_id)
    {
        $streamer_id = LiveRoom::findOne(['id' => $room_id])['streamer_id'];
        $LiveRoomUserModel = LiveRoomUser::findAll(['room_id' => $room_id, 'status' => 1]);
        $count = count($LiveRoomUserModel);
        if ($count == 1 && $LiveRoomUserModel[0]['user_id'] == $streamer_id)
            return true;
        return false;

    }


}
