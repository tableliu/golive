<?php

namespace app\models;

use app\common\Consts;
use app\components\behaviors\LiveRoomStatusBehavior;
use app\controllers\LivePushController;
use app\helpers\CosHelper;
use app\helpers\EasemobHelper;
use app\models\closeRoomDelay\InterfaceCloseRoomDelay;
use app\queues\LiveRoomCloseAfterStreamerLeave;
use app\queues\LiveRoomCloseOnlyStreamer;
use app\queues\LiveRoomCloseTimeOut;
use Yii;
use yii\base\InvalidCallException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\db\Query;

/**
 * This is the model class for table "live_room".
 *
 * @property int $id
 * @property int $updated_by
 * @property int $created_by
 * @property string $updated_at
 * @property string $created_at
 * @property string $guid
 * @property string $name
 * @property string $url
 * @property int $streamer_id
 * @property int $status
 * @property string $closed_at
 */
class LiveRoom extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */

    public static function tableName()
    {
        return 'live_room';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => 1],
            [['updated_by', 'created_by', 'streamer_id', 'status'], 'integer'],
            [['updated_at', 'created_at', 'closed_at'], 'safe'],
            [['guid', 'streamer_id'], 'required'],
            [['guid'], 'string', 'max' => 128],
            [['name', 'url'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_at' => Yii::t('app', 'Created At'),
            'guid' => Yii::t('app', 'Guid'),
            'name' => Yii::t('app', 'Name'),
            'url' => Yii::t('app', 'Url'),
            'streamer_id' => Yii::t('app', 'Streamer ID'),
            'status' => Yii::t('app', 'Status'),
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
            LiveRoomStatusBehavior::className()
        ];
    }

    public function getStreamerProfile()
    {
        return $this->hasOne(Profile::className(), ['user_id' => 'streamer_id']);
    }

    public function getStreamerUser()
    {
        return $this->hasOne(User::className(), ['id' => 'streamer_id']);
    }

    /**
     * @param Online $streamer
     * @param Online $watcher
     * @return LiveRoom|null
     * @throws \yii\base\Exception
     */
    public static function initRoomForPair($streamer, $watcher)
    {
        $room_streamer = LiveRoomUser::findOne([
            'user_id' => $streamer->user_id,
            'user_client_type' => Consts::LIVE_USER_TYPE_MOBILE,
            'status' => 1]);

        // one user can be only one streamer
        if (isset($room_streamer)) {
            self::userLeave($room_streamer->room_id, $streamer);
        }

        $db = LiveRoom::getDb();
        $room = new LiveRoom([
            'guid' => Yii::$app->security->generateRandomString(),
            'name' => $streamer->profile->full_name . '-' . date('H:i', time()),
            'streamer_id' => $streamer->user_id,
        ]);

        $transaction = $db->beginTransaction();
        try {
            if ($room->save() &&
                self::userEnter($room->id, $streamer) &&
                self::userEnter($room->id, $watcher)) {
                // conference
                LiveRoomConfr::createConfrForRoom($room->id);
                $transaction->commit();
                self::closeRoomDelay($room->id, 'ROOM_DESTROY_REASON_ROOM_TIMEOUT', Yii::$app->params['LiveRoom.TimeoutSecs']);
                return $room;
            } else {
                $transaction->rollBack();
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return null;
    }

    public static function changeStatus($room_id, $status)
    {
        $room = LiveRoom::findOne($room_id);
        if ($room == null)
            return false;
        $room->status = $status;
        return $room->save(false);
    }

    /**
     * @param $room_id
     * @param null $client_type
     * @param null $status
     * @return array
     */
    public static function getRoomUserIds($room_id, $client_type = null, $status = null)
    {
        $query = LiveRoomUser::find()
            ->select(['user_id'])
            ->where(['room_id' => $room_id]);
        if ($client_type !== null)
            $query = $query->andWhere(['user_client_type' => $client_type]);
        if ($status !== null)
            $query = $query->andWhere(['status' => $status]);

        return $query->asArray()->column();
    }

    /**
     * @param $room_id
     * @param null|int $status
     * @return array
     */
    public static function getWebUserIds($room_id, $status = null)
    {
        $query = LiveRoomUser::find()->select(['user_id'])->where(['room_id' => $room_id, 'user_client_type' => Consts::LIVE_USER_TYPE_WEB]);
        if ($status !== null)
            $query = $query->andWhere(['status' => $status]);

        return $query->asArray()->column();
    }

    /**
     * @param $room_id
     * @param null|int $status
     * @return array
     */
    public static function getMobileUserIds($room_id, $status = null)
    {
        $query = LiveRoomUser::find()
            ->select(['user_id'])
            ->Where(['room_id' => $room_id, 'user_client_type' => Consts::LIVE_USER_TYPE_MOBILE]);
        if ($status !== null)
            $query = $query->andWhere(['status' => $status]);

        return $query->asArray()->column();
    }

    /**
     * @param $room_id
     * @param $enter_user Online
     * @return bool
     */
    public static function userEnter($room_id, $enter_user)
    {
        if (empty(LiveRoom::findOne(['id' => $room_id, 'status' => 1])))
            return false;

        $liveRoomUser = LiveRoomUser::findOne([
            'room_id' => $room_id,
            'user_id' => $enter_user->user_id
        ]);
        if (isset($liveRoomUser) && $liveRoomUser->status == 1)
            return true;
        if (isset($liveRoomUser) && $liveRoomUser->status == 0) {
            $liveRoomUser->user_wamp_session_id = $enter_user->wamp_session_id;
            $liveRoomUser->user_id = $enter_user->user_id;
            $liveRoomUser->user_client_type = $enter_user->client_type;
            $liveRoomUser->status = 1;
        } else {
            $liveRoomUser = new LiveRoomUser([
                'room_id' => $room_id,
                'user_id' => $enter_user->user_id,
                'user_session_id' => $enter_user->session_id,
                'user_wamp_session_id' => $enter_user->wamp_session_id,
                'user_client_type' => $enter_user->client_type
            ]);
        }

        if ($liveRoomUser->save()) {
            RoomUserLog::saveRoomUserLog($room_id, $enter_user->user_id, RoomUserLog::ROOM_USER_ENTER_ACTION);
            $is_streamer = LiveRoom::isStreamer($room_id, $enter_user->user_id);
            LivePushController::pushWebUserEnterLeave($room_id, $enter_user, LiveRoom::getWebUserIds($room_id, 1), 1, $is_streamer);
            \app\modules\onsite\controllers\LivePushController::pushMobileUserEnterLeave($room_id, $enter_user, LiveRoom::getMobileUserIds($room_id, 1), 1, $is_streamer);
            return true;
        } else
            return false;
    }

    /**
     * @param $room_id
     * @param Online $leave_user
     */
    public static function userLeave($room_id, $leave_user)
    {
        if (!LiveRoomUser::changeStatus(0, $room_id, $leave_user->user_id))
            return;
        // no one in this room ?
        if (empty(LiveRoomUser::findOne(['room_id' => $room_id, 'status' => 1])))
            self::closeRoom($room_id, 'ROOM_DESTROY_REASON_ROOM_CLOSE');
        else {
            $is_streamer = LiveRoom::isStreamer($room_id, $leave_user->user_id);
            if ($is_streamer) {
                self::closeRoomDelay($room_id, 'ROOM_DESTROY_REASON_ROOM_STREAMER_LEAVE', Yii::$app->params['LiveRoom.StreamerLeaveSecs']);
            } else {
                $is_only_streamer = LiveRoomUser::isOnlyStreamer($room_id);
                if ($is_only_streamer)
                    self::closeRoomDelay($room_id, 'ROOM_DESTROY_REASON_ROOM_ONLY_STREAMER', Yii::$app->params['LiveRoom.OnlyStreamerSecs']);
            }
            $web_user_ids = LiveRoom::getRoomUserIds($room_id, Consts::LIVE_USER_TYPE_WEB, 1);
            $mobile_user_ids = LiveRoom::getRoomUserIds($room_id, Consts::LIVE_USER_TYPE_MOBILE, 1);
            LivePushController::pushWebUserEnterLeave($room_id, $leave_user, $web_user_ids, 0, $is_streamer);
            \app\modules\onsite\controllers\LivePushController::pushMobileUserEnterLeave($room_id, $leave_user, $mobile_user_ids, 0, $is_streamer);
        }
        RoomUserLog::saveRoomUserLog($room_id, $leave_user->user_id, RoomUserLog::ROOM_USER_LEAVE_ACTION);
    }

    public static function closeRoom($room_id, $reason)
    {
        // 房间必须没有活动用户才允许关闭
        if (!empty(LiveRoom::getRoomUserIds($room_id, null, 1)))
            throw new InvalidCallException('No user should left in this room before closed.');

        // 不删除房间以及进入过房间的用户交叉表数据
        if (!LiveRoom::changeStatus($room_id, 0))
            return false;

        // destroy conference
        LiveRoomConfr::destroyConference($room_id);

        // push
        $web_user_ids = LiveRoom::getRoomUserIds($room_id, Consts::LIVE_USER_TYPE_WEB);
        $mobile_user_ids = LiveRoom::getRoomUserIds($room_id, Consts::LIVE_USER_TYPE_MOBILE);
        LivePushController::pushRoomDestroy($web_user_ids, $room_id, $reason);
        \app\modules\onsite\controllers\LivePushController::pushMobileRoomDestroy($mobile_user_ids, $room_id, $reason);

        return true;
    }

    public static function closeRoomDelay($room_id, $reason, $delay_sec)
    {
        $pid = Yii::$app->settings->getCurrentPid();
        /**
         * @var $jobs [];
         */
        $jobs = Yii::$app->params['Room.Destroy.Reason'][$reason]['jobs'];
        foreach ($jobs as $job) {
            /**
             * @var $job InterfaceCloseRoomDelay;
             */
            $class = new $job;
            $class->closeRoomDelay($room_id, $reason, $delay_sec, $pid);
        }
        return true;

    }

    public static function deleteRoom($room_id)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $liveMsgModels = LiveMsg::find()->where(['room_id' => $room_id])->all();
            foreach ($liveMsgModels as $liveMsgModel) {
                $type = $liveMsgModel->type;
                if ($type == "image" || $type == "file") {
                    $arr_value = array_slice(explode("/", $liveMsgModel->value), -2);
                    $key = implode("/", $arr_value);
                    CosHelper::deleteCosObject($key, 'live_msg_file');
                }
                $liveMsgModel->delete();
            }
            LiveRoomUser::deleteAll(['room_id' => $room_id]);
            if (LiveRoom::findOne($room_id)->delete() !== false) {
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
        }
        return;
    }

    public static function isStreamer($room_id, $user_id)
    {
        $liveRoomModel = LiveRoom::findOne(['id' => $room_id]);
        $streamer_id = $liveRoomModel->streamer_id;
        if ($streamer_id == $user_id)
            return true;
        return false;

    }
}
