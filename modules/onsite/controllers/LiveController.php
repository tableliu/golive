<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/9
 * Time: 14:24
 */

namespace app\modules\onsite\controllers;


use app\common\Consts;
use app\common\RestResult;
use app\components\rest\BaseAPIController;
use app\models\EasemobUserOnline;
use app\models\LiveMsg;
use app\models\LiveRoom;
use app\models\LiveRoomConfr;
use app\models\LiveRoomUser;
use app\models\Online;
use Yii;
use yii\filters\VerbFilter;

class LiveController extends BaseAPIController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'respond-watcher-call' => ['post'],
            ],
        ];
        $behaviors['access'] = [
            'class' => 'app\components\filters\RestAccessControl',
            'allowActions' => [
                'respond-watcher-call',
                'call-watcher-cancel'
            ]
        ];
        return $behaviors;
    }

    public function actionRespondWatcherCall()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR
            ]);

        $call_user_wamp_session_id = Yii::$app->request->post('call_user_wamp_session_id');
        $is_accept = Yii::$app->request->post('is_accept');
        if (!isset($call_user_wamp_session_id) || !isset($is_accept)) {
            return $result;
        }

        // create room
        $created_room_id = "";
        if (intval($is_accept) == 1) {
            $watcher_online_user = Online::findOne(['wamp_session_id' => $call_user_wamp_session_id]);
            $streamer_online_user = Online::findOne(['user_id' => Yii::$app->user->id]);
            if (!isset($watcher_online_user) || !isset($streamer_online_user))
                return $result;

            $room = LiveRoom::initRoomForPair($streamer_online_user, $watcher_online_user);
            if (!isset($room)) {
                return $result;
            }
            $created_room_id = $room->id;
            $sent = LivePushController::pushStreamerRespondWatcherCall($call_user_wamp_session_id, Yii::$app->user->identity, intval($is_accept), $room->id);
        } elseif (intval($is_accept) == 0) {
            $sent = LivePushController::pushStreamerRespondWatcherCall($call_user_wamp_session_id, Yii::$app->user->identity, intval($is_accept));
        } else {
            $sent = false;
        }
        if ($sent)
            $result = Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_OK,
                    'data' => [
                        'created_room_id' => $created_room_id
                    ],
                    'msg' => '',
                ]);
        return $result;
    }

    //获取web端在线用户列表
    public function actionWebOnlineUsers()
    {
        $data = [];
        foreach (Online::getOnlineUsers(Consts::LIVE_USER_TYPE_WEB) as $online) {
            $data[] = [
                "id" => $online->id,
                "user_id" => $online->user_id,
                "name" => $online->user->username,
                'full_name' => $online->profile->full_name,
                'avatar' => $online->profile->getAvatarUrl(),
                'online_type' => $online->client_type
            ];
        }

        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $data,
                'msg' => "",
            ]);
    }

    //呼叫某web端用户
    public function actionCallWatcher()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);
        $called_user_id = Yii::$app->request->post('called_user_id');
        $call_user = Online::findOne(
            [
                'user_id' => Yii::$app->user->id,
            ]
        );
        if (!isset($call_user) ||
            !LivePushController::pushCallWeb($call_user, $called_user_id))
            return $result;

        $result->code = Consts::REST_OK;
        return $result;
    }

    //取消web端用户
    public function actionCallWatcherCancel()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR
            ]);

        $called_user_id = Yii::$app->request->post('called_user_id');
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
        $sent = LivePushController::pushCalledCanceled($called_user_id, $call_user);
        if ($sent)
            $result = Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_OK,
                    'data' => 1,
                    'msg' => '',
                ]);
        return $result;

    }

    //获取房间信息
    public function actionRoomInfo($room_id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);

        /**
         * @var LiveRoom $room
         */
        $room = LiveRoom::find()
            ->where([
                'id' => $room_id,
                'status' => 1
            ])
            ->one();
        if (empty($room))
            return $result;

        $streamer = LiveRoomUser::findOne(
            [
                'room_id' => $room_id,
                'user_id' => $room->streamer_id
            ]);
        $streamer ? $streamer_data = [
            "id" => $streamer->user_id,
            "name" => $streamer->profile->full_name,
            'avatar' => $streamer->profile->getAvatarUrl(),
        ] : $streamer_data = [];

        // confr
        $confr = LiveRoomConfr::getConference($room_id);
        //confr_user
        $wamp_session_id = empty($wamp_session_id) ?
            Online::findOne(['user_id' => Yii::$app->user->identity->id])->wamp_session_id :
            $wamp_session_id;
        $confr_user = EasemobUserOnline::getConfrUserForRoom($room_id, $wamp_session_id);

        $newModel = [
            "id" => $room->id,
            "name" => $room->name,
            "url" => $room->url,
            "created_at" => $room->created_at,
            'streamer' => $streamer_data,
            'confr' => [
                'id' => $confr->confr_id,
                'name' => $confr->confr_name,
                'password' => $confr->confr_password,
                'type' => $confr->type
            ],
            'confr_user' => [
                'name' => $confr_user->username,
                'uuid' => $confr_user->uuid,
                'token' => $confr_user->access_token,
                'role' => Yii::$app->params['LiveStreaming.Role.TALKER']
            ]
        ];

        return $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $newModel,
                'msg' => '',
            ]);

    }

    //房间人员
    public function actionRoomUsers($room_id)
    {
        $room_users = LiveRoomUser::findAll(['room_id' => $room_id, 'status' => 1]);
        $new_data = [];
        foreach ($room_users as $room_user) {
            $data['user_id'] = $room_user->user_id;
            $data['user_name'] = $room_user->user->username;
            $data['full_name'] = $room_user->profile->full_name;
            $data['avatar'] = $room_user->profile->getAvatarUrl();
            $data['online_type'] = $room_user->user_client_type;
            $new_data[] = $data;

        }
        return $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $new_data,
                'msg' => '',
            ]);

    }

    //播主离开房间
    public function actionLeave($room_id)
    {
        $room_user = LiveRoomUser::find()
            ->where([
                'room_id' => $room_id,
                'user_id' => Yii::$app->user->id])
            ->one();
        if (empty($room_user))
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_SERVER_ERROR,
                    'msg' => "You are not in this room.",
                ]);

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
        LiveRoom::userLeave($room_id, $call_user);
        return $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => 1,
                'msg' => '',
            ]);

    }

    //播主返回房间
    public function actionBack($room_id, $wamp_session_id)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);
        /**
         * @var LiveRoom $room
         */
        $room = LiveRoom::find()
            ->where([
                'id' => $room_id,
                'status' => 1
            ])
            ->one();
        if (empty($room))
            return $result;

        /**
         * @var Online $onlie_user
         */
        $onlie_user = Online::find()
            ->where(['wamp_session_id' => $wamp_session_id])
            ->one();
        if (empty($onlie_user))
            return $result;

        $streamer = LiveRoomUser::findOne(
            [
                'room_id' => $room_id,
                'user_id' => $room->streamer_id
            ]);
        $streamer ? $streamer_data = [
            "id" => $streamer->user_id,
            "name" => $streamer->profile->full_name,
            'avatar' => $streamer->profile->getAvatarUrl(),
        ] : $streamer_data = [];
        // confr
        $confr = LiveRoomConfr::getConference($room_id);
        //confr_user
        $confr_user = EasemobUserOnline::getConfrUserForRoom($room_id, $wamp_session_id);
        if (LiveRoom::userEnter($room_id, $onlie_user)) {
            $newModel = [
                "id" => $room->id,
                "name" => $room->name,
                "url" => $room->url,
                "created_at" => $room->created_at,
                'streamer' => $streamer_data,
                'confr' => [
                    'id' => $confr->confr_id,
                    'name' => $confr->confr_name,
                    'password' => $confr->confr_password,
                    'type' => $confr->type
                ],
                'confr_user' => [
                    'name' => $confr_user->username,
                    'uuid' => $confr_user->uuid,
                    'token' => $confr_user->access_token,
                    'role' => Yii::$app->params['LiveStreaming.Role.TALKER']
                ]
            ];

            return $result = Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_OK,
                    'data' => $newModel
                ]);
        }
        return $result;
    }

    public function actionLivingRoomHistory()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK
            ]);
        $room_ids = LiveRoomUser::find()
            ->select(['room_id'])
            ->where(['user_id' => Yii::$app->user->id])
            ->distinct()
            ->all();
        $models = [];
        foreach ($room_ids as $id) {
            $room = LiveRoom::findOne(['id' => $id['room_id'],
                'status' => 1]);
            if (empty($room))
                continue;
            $streamer = $room->streamerProfile;
            $models[] = [
                'id' => $room->id,
                'name' => $room->name,
                'url' => $room->url,
                'create_at' => $room->created_at,
                'streamer' => [
                    'id' => $streamer->user_id,
                    'name' => $streamer->full_name,
                    'avatar' => $streamer->getAvatarUrl()
                ]
            ];
        }

        $result->data = $models;
        return $result;
    }

    public function actionChatRecords()
    {
        $live_msgs = LiveMsg::Search(Yii::$app->request->queryParams);
        $data = [];
        foreach ($live_msgs['model'] as $live_msg) {
            $array = [
                "id" => $live_msg->id,
                "user_id" => $live_msg->profile->user_id,
                "full_name" => $live_msg->profile->full_name,
                "user_name" => $live_msg->user->username,
                "avatar" => $live_msg->profile->getAvatarUrl(),
                "type" => $live_msg->type,
                "value" => $live_msg->value,
                'file_name' => $live_msg->file_name,
                "created_at" => $live_msg->created_at
            ];
            $data[] = $array;

        }
        $page = [
            'earliest_chat_id' => $live_msgs['earliest_chat_id'],
            'pager' => $live_msgs['pager'],
        ];
        return $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => [
                    'list_data' => $data,
                    'page' => $page
                ],
                'msg' => '',
            ]);


    }


}