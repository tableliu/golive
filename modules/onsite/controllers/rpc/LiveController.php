<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/2
 * Time: 10:49
 */

namespace app\modules\onsite\controllers\rpc;

use app\common\Consts;
use app\common\RestResult;
use app\components\socket\rpc\Controller;
use app\models\Online;
use app\models\UserLocation;
use app\modules\onsite\controllers\LivePushController;
use app\models\LiveMsg;
use app\models\LiveRoom;
use app\models\LiveRoomUser;
use Yii;

class LiveController extends Controller
{

    const ERRCODE_SEND_MSG_NO_ROOM = 1;
    const ERRCODE_SEND_MSG_EMPTY_MSG = 2;

    public function actionSendMsg($room_id, $type, $msg, $file_name = null)
    {
        $call_user = Online::findOne([
            'user_id' => Yii::$app->user->id,
        ]);
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

        $room = LiveRoom::findOne([
            'id' => $room_id,
            'status' => 1
        ]);
        if (!isset($room)) {
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_DATA_ERROR,
                    'errcode' => $this::ERRCODE_SEND_MSG_NO_ROOM,
                    'msg' => Yii::t('app', 'No room find.')
                ]);
        }

        $model = new LiveMsg();
        $model->room_id = $room_id;
        $model->type = $type;
        $model->file_name = $file_name;
        $model->value = $msg;

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
        $model->user_type = Consts::LIVE_USER_TYPE_MOBILE;
        $model->save();
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $model->id
            ]);


    }
}