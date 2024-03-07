<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/2
 * Time: 10:49
 */

namespace app\controllers\rpc;

use app\common\Consts;
use app\common\RestResult;
use app\components\socket\rpc\Controller;
use app\controllers\LivePushController;
use app\models\LiveMsg;
use app\models\LiveRoom;
use app\models\LiveRoomUser;
use app\models\Online;
use app\models\UserLocation;
use Yii;
use yii\filters\VerbFilter;

class LiveController extends Controller
{
    const ERRCODE_SEND_MSG_NO_ROOM = 1;
    const ERRCODE_SEND_MSG_EMPTY_MSG = 2;

    //发送消息
    public function actionSendMsg($room_id, $type, $msg, $file_name = null)
    {
        $call_user = Online::findOne(
            [
                'user_id' => Yii::$app->user->id,
            ]
        );
        if (!isset($call_user))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "self-user not online.",
                ]);
        if (empty(trim($msg)))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_SEND_MSG_EMPTY_MSG,
                    'msg' => Yii::t('app', 'Cannot send. Message is empty.')
                ]);

        $room = LiveRoom::findOne(
            [
                'id' => $room_id,
                'status' => 1
            ]);
        if (!isset($room))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_SEND_MSG_NO_ROOM,
                    'msg' => Yii::t('app', 'No room find.')
                ]);
        $model = new LiveMsg();
        $model->room_id = $room_id;
        $model->type = $type;
        $model->value = $msg;
        $model->file_name = $file_name;

        if ($model->send($call_user))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_OK,
                    'data' => [
                        'id' => $model->id,
                        'created_at' => $model->created_at
                    ]
                ]);

        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);
    }

    public function getMobileUserIds($room_id)
    {
        return LiveRoomUser::find()
            ->select(['user_id'])
            ->Where(['room_id' => $room_id, 'user_client_type' => Consts::LIVE_USER_TYPE_MOBILE, 'status' => 1])
            ->asarray()
            ->column();

    }


    public function actionUpdateLocation($long, $lat, $wamp_session_id)
    {
        $onlineUser = Online::findOne(
            [
                'user_id' => Yii::$app->user->id,
            ]
        );
        if (!isset($onlineUser))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "user not online.",
                ]);
        $model = new UserLocation();
        $model->user_id = Yii::$app->user->id;
        $model->longitude = $long;
        $model->latitude = $lat;
        $model->wamp_session_id = $wamp_session_id;
        $model->user_type = Consts::LIVE_USER_TYPE_WEB;

        $model->save();
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $model->id
            ]);


    }

    public function actionRectMark($x, $y, $width, $height, $room_id)
    {
        $room = LiveRoom::findOne(
            [
                'id' => $room_id,
                'status' => 1
            ]);
        if (!isset($room))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_SEND_MSG_NO_ROOM,
                    'msg' => Yii::t('app', 'No room find.')
                ]);
        $streamer = LiveRoomUser::findOne(
            [
                'room_id' => $room->id,
                'user_id' => $room->streamer_id,
                'status' => 1,
            ]);

        if (!isset($streamer))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "streamer not in this room.",
                ]);
        $streamer_online = Online::findOne([
            'wamp_session_id' => $streamer->user_wamp_session_id
        ]);
        if (!isset($streamer_online))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "streamer not online.",
                ]);

        $send = LivePushController::pushRectMark($x, $y, $width, $height, $room->id, $streamer_online->wamp_session_id);
        if ($send)
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_OK,
                    'data' => ""
                ]);

        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);

    }

    public function actionMarkCancel($room_id)
    {
        $room = LiveRoom::findOne(
            [
                'id' => $room_id,
                'status' => 1
            ]);
        if (!isset($room))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_SEND_MSG_NO_ROOM,
                    'msg' => Yii::t('app', 'No room find.')
                ]);
        $streamer = LiveRoomUser::findOne(
            [
                'room_id' => $room->id,
                'user_id' => $room->streamer_id,
                'status' => 1,
            ]);

        if (!isset($streamer))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "streamer not in this room.",
                ]);
        $streamer_online = Online::findOne([
            'wamp_session_id' => $streamer->user_wamp_session_id
        ]);
        if (!isset($streamer_online))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "streamer not online.",
                ]);

        $send = LivePushController::pushRectCancel($room->id, $streamer_online->wamp_session_id);
        if ($send)
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_OK,
                    'data' => ""
                ]);
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);

    }
}