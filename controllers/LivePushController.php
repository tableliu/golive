<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/12
 * Time: 18:00
 */

namespace app\controllers;


use app\common\Consts;
use app\helpers\SocketHelper;
use app\helpers\ZmqEntryData;
use app\models\LiveRoom;
use app\models\LiveMsg;
use app\models\LiveRoomUser;
use app\models\Online;
use app\models\UserLocation;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\User;

class LivePushController
{
    /**
     * @param Online $call_user
     * @param $called_user_id
     * @return bool
     */
    public static function pushCallMobile($call_user, $called_user_id)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['user_id' => $called_user_id])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->one();
        $whitelist = $whitelist_sql['wamp_session_id'];
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/called',
            'data' => [
                'call_user_id' => $call_user->user_id,
                'call_user_name' => $call_user->user->username,
                'call_user_avatar' => $call_user->profile->getAvatarUrl(),
                'call_user_wamp_session_id' => $call_user->wamp_session_id
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
     * @param array $called_user_ids
     * @param Online $invite_user
     * @param LiveRoom $room
     * @return bool
     */
    public static function pushInvited($called_user_ids, $invite_user, $room)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $called_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->all();
        $whitelist = [];
        foreach ($whitelist_sql as $item) {
            $whitelist[] = $item['wamp_session_id'];
        }
        if (empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/invited',
            'data' => [
                'invite_user_id' => $invite_user->user_id,
                'invite_user_name' => $invite_user->user->username,
                'invite_user_avatar' => $invite_user->profile->getAvatarUrl(),
                'invite_user_wamp_session_id' => $invite_user->wamp_session_id,
                'room_id' => $room->id,
                'room_name' => $room->name,
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
     * live/m/push/watcher-respond-call
     * @param $call_user_id
     * @param \yii\web\IdentityInterface $called_user
     * @param bool $is_accept
     * @param int $created_room_id
     * @return bool
     */
    public static function pushWatcherRespondStreamerCall($call_user_id, $called_user, $is_accept, $created_room_id = 0)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['user_id' => $call_user_id])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->one();
        if (!isset($whitelist_sql))
            return false;
        $whitelist = $whitelist_sql['wamp_session_id'];
        if (empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/watcher-respond-call',
            'data' => [
                'called_user_id' => $called_user->id,
                'called_user_name' => $called_user->username,
                'called_user_avatar' => $called_user->profile->getAvatarUrl(),
                'is_accept' => $is_accept,
                'created_room_id' => $created_room_id,
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
     * live/m/push/called-canceled
     * @param integer $called_user_id
     * @param integer $call_user_wamp_session_id
     * @param User $call_user
     * @return bool
     */
    public static function pushCalledCanceled($called_user_id, $call_user_wamp_session_id, $call_user)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['user_id' => $called_user_id])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_MOBILE])
            ->one();
        if (!isset($whitelist_sql))
            return false;
        $whitelist = $whitelist_sql['wamp_session_id'];
        if (empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/called-canceled',
            'data' => [
                'call_user_id' => $call_user->id,
                'call_user_wamp_session_id' => $call_user_wamp_session_id
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
     * @param integer $invite_user_wamp_session_id
     * @param \yii\web\IdentityInterface $invited_user
     * @param bool $is_accept
     * @param integer $room_id
     * @return bool
     */
    public static function pushRespondAttendInvite($invite_user_wamp_session_id, $invited_user, $is_accept, $room_id)
    {
        $whitelist = Online::find()
            ->where(['wamp_session_id' => $invite_user_wamp_session_id])
            ->one();
        if (empty($whitelist))
            return false;

        $msg = [
            'uri' => 'live/push/respond-attend-invite',
            'data' => [
                'called_user_id' => $invited_user->id,
                'called_user_name' => $invited_user->username,
                'called_user_avatar' => $invited_user->profile->getAvatarUrl(),
                'is_accept' => $is_accept,
                'room_id' => $room_id,
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
     * live/push/room-destroy
     * @param array $web_online_user_ids
     * @param  integer $room_id
     * @return bool
     */
    public static function pushRoomDestroy($web_online_user_ids, $room_id, $reason)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $web_online_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/room-destroy',
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
     * Live/push/user-enter-leave
     * @param Online $call_user
     * @param array $web_user_ids
     * @param bool $is_enter
     * @return bool
     */
    public static function pushWebUserEnterLeave($room_id, $call_user, $web_user_ids, $is_enter, $is_streamer)
    {
        if (empty(LiveRoom::findOne(['id' => $room_id, 'status' => 1])))
            return false;

        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $web_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/user-enter-leave',
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
     * live/push/chat-sent
     * @param LiveMsg $livemsg
     * @param array $web_user_ids
     * @param Online $user
     * @param bool $exclude_self_push
     * @return bool
     */
    public static function pushWebChatSent($livemsg, $web_user_ids, $user, $exclude_self_push = true)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $web_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if ($exclude_self_push)
            $whitelist = array_diff($whitelist, [$user->wamp_session_id]);

        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/chat-sent',
            'data' => [
                "id" => $livemsg->id,
                "room_id" => $livemsg->room_id,
                "user_id" => $user->user->id,
                "file_name" => $livemsg->file_name,
                "user_name" => $user->user->username,
                "user_avatar" => $user->user->profile->getAvatarUrl(),
                "full_name" => $user->user->profile->full_name,
                "type" => $livemsg->type,
                "value" => $livemsg->value,
                "client_type" => $user->client_type,
                "created_at" => $livemsg->created_at

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
     * @param array $invited_cancel_user_ids_int
     * @param int $invite_user_id
     * @param int $room_id
     * @param string $invite_user_wamp_session_id
     * @return bool
     */
    public function pushInvitedCanceled($invited_cancel_user_ids_int, $invite_user_id, $room_id, $invite_user_wamp_session_id)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $invited_cancel_user_ids_int])
            //            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
            ->all();
        $whitelist = ArrayHelper::getColumn($whitelist_sql, 'wamp_session_id');
        if (!isset($whitelist_sql) || empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/push/invited-canceled',
            'data' => [
                "room_id" => $room_id,
                "invite_user_id" => $invite_user_id,
                "invite_user_wamp_session_id" => $invite_user_wamp_session_id,
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
     * /live/push/user-online-offline
     * @param User $userModel
     * @param bool $is_online
     * @param string $client_type
     * @param array $web_user_ids
     * @return bool
     */
    public function pushWebOnlineOffline($userModel, $is_online, $client_type, $web_user_ids)
    {
        $whitelist_sql = Online::find()
            ->select(['wamp_session_id'])
            ->andWhere(['in', 'user_id', $web_user_ids])
            ->andWhere(['client_type' => Consts::LIVE_USER_TYPE_WEB])
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
            'uri' => 'live/push/user-online-offline',
            'data' => [
                "user_id" => $userModel->id,
                "is_online" => $is_online,
                "user_name" => $userModel->username,
                "full_name" => $userModel->profile->full_name,
                "client_type" => $client_type,
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

    /**
     * @param $x integer
     * @param $y integer
     * @param $width integer
     * @param $height integer
     * @param $room_id
     * @param $wamp_session_id
     * @return bool
     */
    public static function pushRectMark($x, $y, $width, $height, $room_id, $wamp_session_id)
    {
        $whitelist = [$wamp_session_id];
        if (empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/rect-mark',
            'data' => [
                "x" => $x,
                "y" => $y,
                "width" => $width,
                "height" => $height,
                'room_id' => $room_id
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
     * @param $streamer_id integer
     * @return bool
     */
    public static function pushRectCancel($room_id, $wamp_session_id)
    {
        $whitelist = [$wamp_session_id];
        if (empty($whitelist))
            return false;
        $msg = [
            'uri' => 'live/m/push/rect-cancel',
            'data' => [
                'room_id' => $room_id
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