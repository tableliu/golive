<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/12
 * Time: 18:30
 */

namespace app\modules\onsite\controllers;


use app\common\Consts;
use app\helpers\SocketHelper;
use app\helpers\ZmqEntryData;
use app\models\LiveMsg;
use app\models\LiveRoom;
use app\models\Online;
use app\models\UserLocation;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\User;

class LivePushController
{
    /**
     * live/push/streamer-respond-call
     * @param $call_user_wamp_session_id
     * @param \yii\web\IdentityInterface $called_user
     * @param $is_accept
     * @param int $created_room_id
     * @return bool
     */
    public static function pushStreamerRespondWatcherCall($call_user_wamp_session_id, $called_user, $is_accept, $created_room_id = 0)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            //            ->andWhere(['user_id' => $call_user_id])
            ->andWhere(['wamp_session_id' => $call_user_wamp_session_id])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->one();
        if (!isset($whitelist_sql))
            return false;
        $whitelist = $whitelist_sql['wamp_session_id'];
        if (empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/streamer-respond-call',
            'data' => [
                'called_user_id' => $called_user->id,
                'called_user_name' => $called_user->username,
                'called_user_avatar' => $called_user->profile->getAvatarUrl(),
                'is_accept' => $is_accept,
                'created_room_id' => $created_room_id
            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;
    }


    /**
     * live/push/called
     * @param Online $call_user
     * @param integer $called_user_id
     * @return bool
     */
    public static function pushCallWeb($call_user, $called_user_id)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['user_id' => $called_user_id])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->one();
        $whitelist = $whitelist_sql['wamp_session_id'];
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/called',
            'data' => [
                'call_user_id' => $call_user->user_id,
                'call_user_name' => $call_user->user->username,
                'call_user_avatar' => $call_user->profile->getAvatarUrl(),
            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;
    }


    /**
     * live/push/called-canceled
     * @param  integer $called_user_id
     * @param  Online $call_user
     * @return bool
     */
    public static function pushCalledCanceled($called_user_id, $call_user)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['user_id' => $called_user_id])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/called-canceled',
            'data' => [
                'call_user_id' => $call_user->user_id,
                'call_user_name' => $call_user->user->username,
                'call_user_avatar' => $call_user->profile->getAvatarUrl(),
            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;

    }


    /**
     * live/m/push/user-enter-leave
     * @param online $call_user
     * @param array $mobile_user_ids
     * @param bool $is_enter
     * @return bool
     */
    public static function pushMobileUserEnterLeave($room_id, $call_user, $mobile_user_ids, $is_enter, $is_streamer)
    {
        if (empty(LiveRoom::findOne(['id' => $room_id, 'status' => 1])))
            return false;

        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $mobile_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->one();
        $whitelist = $whitelist_sql['wamp_session_id'];
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/user-enter-leave',
            'data' => [
                'room_id' => $room_id,
                'user_id' => $call_user->user_id,
                'is_enter' => $is_enter,
                'client_type' => $call_user->client_type,
                'user_name' => $call_user->user->username,
                'full_name' => $call_user->user->profile->full_name,
                'user_avatar' => $call_user->user->profile->getAvatarUrl(),
                'is_streamer' => $is_streamer ? 1 : 0
            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;
    }

    /**
     * live/m/push/chat-sent
     * @param LiveMsg $LiveMsg
     * @param array $mobile_user_ids
     * @param Online $user
     * @param $exclude_self_push
     * @return bool
     */
    public static function pushMobileChatSent($LiveMsg, $mobile_user_ids, $user, $exclude_self_push)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $mobile_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if ($exclude_self_push)
            $whitelist = array_diff($whitelist, [$user->wamp_session_id]);

        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/chat-sent',
            'data' => [
                "id" => $LiveMsg->id,
                "room_id" => $LiveMsg->room_id,
                "user_id" => $user->user->id,
                "file_name" => $LiveMsg->file_name,
                "user_name" => $user->user->username,
                "user_avatar" => $user->user->profile->getAvatarUrl(),
                "full_name" => $user->user->profile->full_name,
                "type" => $LiveMsg->type,
                "value" => $LiveMsg->value,
                "client_type" => $user->client_type,
                "created_at" => $LiveMsg->created_at

            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;

    }


    /**
     * live/m/push/room-destroy
     * @param $mobile_user_ids
     * @param  integer $room_id
     * @return bool
     */
    public static function pushMobileRoomDestroy($mobile_user_ids, $room_id, $reason)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $mobile_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/room-destroy',
            'data' => [
                'room_id' => $room_id,
                "reason" => Yii::$app->params['Room.Destroy.Reason'][$reason]
            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;

    }

    /**
     * live/m/push/user-online-offline
     * @param User $userModel
     * @param bool $is_online
     * @param string $client_type
     * @param array $mobile_user_ids
     * @return bool
     */
    public function pushMobileOnlineOffline($userModel, $is_online, $client_type, $mobile_user_ids)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $mobile_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $long = null;
        $lat = null;
        if ($is_online == 1) {
            $userLocation = UserLocation::getLastOnlineLocation($userModel->id);
            if (isset($userLocation)) {
                $long = $userLocation->longitude;
                $lat = $userLocation->latitude;
            }
        }
        $msg = [
            'uri' => 'live/m/push/user-online-offline',
            'data' => [
                "user_id" => $userModel->id,
                "is_online" => $is_online,
                "user_name" => $userModel->username,
                "client_type" => $client_type,
                "full_name" => $userModel->profile->full_name,
                "user_avatar" => $userModel->profile->getAvatarUrl(),
                "role" => $userModel->getAssignmentsStr($userModel->id),
                'long' => $long,
                'lat' => $lat
            ]
        ];
        $entryData = new ZmqEntryData([
            'msg' => $msg,
            'whitelist' => $whitelist
        ]);
        $success = true;
        if (SocketHelper::pushMsg($entryData) === false)
            $success = false;
        return $success;
    }


}