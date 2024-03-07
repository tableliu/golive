<?php

namespace app\controllers;


use app\common\Consts;
use app\common\IIPActiveController;
use app\common\RestResult;
use app\helpers\GeoCodingHelper;
use app\helpers\TimeHelper;
use app\models\EasemobUserOnline;
use app\models\LiveMsg;
use app\models\LiveRoom;
use app\models\LiveRoomConfr;
use app\models\LiveRoomSearch;
use app\models\LiveRoomUser;
use app\models\Online;
use app\models\Project;
use app\models\RoomUserLog;
use app\models\UserLocation;
use Yii;
use yii\filters\VerbFilter;

class LiveController extends IIPActiveController
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'invite-watcher' => ['post'],
                'call-streamer' => ['post'],
                'respond-attend-invite' => ['post'],
                'respond-streamer-call' => ['post'],
                'call-streamer-cancel' => ['post']
            ],
        ];
        return $behaviors;
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
            $room = LiveRoom::findOne([
                'id' => $id['room_id'],
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

    public function actionWebOnlineUsers()
    {

        $data = [];
        foreach (Online::getOnlineUsers(Consts::LIVE_USER_TYPE_WEB, false) as $online) {
            $data[] = [
                "id" => $online->id,
                "user_id" => $online->user_id,
                "user_name" => $online->user->username,
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

    public function actionCallStreamer()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);
        $called_user_id = Yii::$app->request->post('called_user_id');
        $call_user_wamp_session_id = Yii::$app->request->post('call_user_wamp_session_id');
        $call_user = Online::findOne(
            [
                'wamp_session_id' => $call_user_wamp_session_id,
                'user_id' => Yii::$app->user->id, // check if the wamp_session_id is the current login user.
            ]
        );
        if (!isset($call_user) ||
            !LivePushController::pushCallMobile($call_user, $called_user_id))
            return $result;
        $result->code = Consts::REST_OK;
        return $result;

    }

    public function actionInviteWatcher()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);

        $invited_user_ids = Yii::$app->request->post('invited_user_ids');
        $room_id = Yii::$app->request->post("room_id");
        $invite_user_wamp_session_id = Yii::$app->request->post('invite_user_wamp_session_id');
        if (!isset($invited_user_ids) || !isset($room_id) || !isset($invite_user_wamp_session_id))
            return $result;

        $room = LiveRoom::findOne(['id' => $room_id, 'status' => 1]);
        if (!isset($room))
            return $result;
        $invite_user = Online::findOne(
            [
                'wamp_session_id' => $invite_user_wamp_session_id,
                'user_id' => Yii::$app->user->id, // check if the wamp_session_id is the current login user.
            ]
        );
        if (!isset($room))
            return $result;

        $invited_user_ids_int = [];
        foreach ($invited_user_ids as $called_user_id) {
            $invited_user_ids_int[] = intval($called_user_id);
        }
        if (!LivePushController::pushInvited($invited_user_ids_int, $invite_user, $room))
            return $result;
        $result->code = Consts::REST_OK;
        return $result;
    }

    public function actionRespondAttendInvite()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR
            ]);
        $invite_user_wamp_session_id = Yii::$app->request->post('invite_user_wamp_session_id');
        $invited_user_wamp_session_id = Yii::$app->request->post('invited_user_wamp_session_id');
        $is_accept = Yii::$app->request->post('is_accept');
        $room_id = Yii::$app->request->post('room_id');
        if (!isset($invited_user_wamp_session_id) || !isset($invite_user_wamp_session_id) || !isset($is_accept) || !isset($room_id)) {
            return $result;
        }
        $is_accept = intval($is_accept);

        $invited_user = Online::findOne(['wamp_session_id' => $invited_user_wamp_session_id]);
        if (!isset($invited_user) || $invited_user->user_id != Yii::$app->user->id)
            return $result;

        if ($is_accept == 1) {
            if (!LiveRoom::userEnter($room_id, $invited_user))
                return $result;
            // push
            LivePushController::pushRespondAttendInvite($invite_user_wamp_session_id, Yii::$app->user->identity, $is_accept, $room_id);
        } elseif ($is_accept == 0) {
            // push
            LivePushController::pushRespondAttendInvite($invite_user_wamp_session_id, Yii::$app->user->identity, $is_accept, $room_id);
        } else {
            return $result;
        }

        $result->code = Consts::REST_OK;
        $result->data = 1;
        return $result;
    }

    //观看者应答
    public function actionRespondStreamerCall()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR
            ]);

        $respond_user_wamp_session_id = Yii::$app->request->post('respond_user_wamp_session_id');
        $call_user_id = Yii::$app->request->post('call_user_id');
        $is_accept = Yii::$app->request->post('is_accept');
        if (!isset($respond_user_wamp_session_id) || !isset($is_accept)) {
            return $result;
        }

        // create room
        $created_room_id = "";
        if (intval($is_accept) == 1) {
            $watcher_online_user = Online::findOne(['wamp_session_id' => $respond_user_wamp_session_id]);
            $streamer_online_user = Online::findOne(['user_id' => $call_user_id]);
            if (!isset($watcher_online_user) || !isset($streamer_online_user))
                return $result;

            $room = LiveRoom::initRoomForPair($streamer_online_user, $watcher_online_user);
            if (!isset($room)) {
                return $result;
            }
            $created_room_id = $room->id;
            $sent = LivePushController::pushWatcherRespondStreamerCall($call_user_id, Yii::$app->user->identity, intval($is_accept), $room->id);
        } elseif (intval($is_accept) == 0) {
            $sent = LivePushController::pushWatcherRespondStreamerCall($call_user_id, Yii::$app->user->identity, intval($is_accept));
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

    //取消呼叫播主
    public function actionCallStreamerCancel()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR
            ]);

        $call_user_wamp_session_id = Yii::$app->request->post('call_user_wamp_session_id');
        $called_user_id = Yii::$app->request->post('called_user_id');
        $call_user = Online::findOne(
            [
                'wamp_session_id' => $call_user_wamp_session_id,
                'user_id' => Yii::$app->user->id, // check if the wamp_session_id is the current login user.
            ]
        );
        if (!isset($call_user)) {
            return $result;
        }

        $sent = LivePushController::pushCalledCanceled($called_user_id, $call_user_wamp_session_id, $call_user);
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

    //观众离开房间
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
                'wamp_session_id' => $room_user->user_wamp_session_id,
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

    //观众返回房间
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

    //获取房间信息
    public function actionRoomInfo($room_id, $wamp_session_id = null)
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
                'file_name' => $live_msg->file_name,
                "value" => $live_msg->value,
                "created_at" => $live_msg->created_at
            ];
            $data[] = $array;

        }
        return $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $data,
                'msg' => '',
            ]);

    }

    public function actionDownload($id)
    {
        $path = Yii::getAlias('@webroot') . '/';
        $model = LiveMsg::findOne($id);
        $url = $model->value;
        $filename = $path . $url;
        if (!file_exists($filename)) {
            return $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_FILE_NOT_EXIST,
                    'data' => "",
                    'msg' => Yii::t('app', 'not exist'),
                ]);
        }
        $file = fopen($filename, "r");
        header('Content-Disposition: attachment; filename=' . $model->file_name);
        header("Content-Type:application/octet-stream");
        echo fread($file, filesize($filename));
        fclose($file);

    }


    public function actionLiveRoomRecord()
    {
        $model = new LiveRoomSearch();
        $newData = [];
        $dataProvider = $model->getRoomList(Yii::$app->request->queryParams);
        $map_key = Yii::$app->params['Baidu.Map.Appkey'];
        foreach ($dataProvider->getModels() as $result) {
            $userLastLocation = UserLocation::getLastOnlineLocation($result['streamer_id']);
            $array['id'] = $result['id'];
            $array['session_time'] = TimeHelper::timeDiff(RoomUserLog::getRoomMeetingLength($result['id'], $result['streamer_id']) * 60);
            $array['site'] = $userLastLocation ? GeoCodingHelper::getAddressComponent($map_key, $userLastLocation['longitude'], $userLastLocation['latitude'], GeoCodingHelper::NO_POIS)['result']['addressComponent']['city'] : null;
            $array['created_at'] = $result['created_at'];
            $array['streamer_name'] = $result['full_name'];
            $array['user_count'] = $result['user_count'];
            $newData[] = $array;
        }
        array_key_exists("page", Yii::$app->request->getQueryParams()) !== false ? $page = Yii::$app->request->getQueryParams()['page'] : $page = 1;
        $perpage = $dataProvider->getCount();
        $count = $dataProvider->getTotalCount();
        if (array_key_exists("sort", Yii::$app->request->getQueryParams())) {
            strpos(Yii::$app->request->getQueryParams()['sort'], '-') !== false ? $sort = substr(Yii::$app->request->getQueryParams()['sort'] . "|SORT_DESC", 1) : $sort = Yii::$app->request->getQueryParams()['sort'] . "|SORT_ASC";
        } else {
            $sort = "id|SORT_DESC";
        }
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => [
                    "paging" => [
                        'page' => $page,
                        'per-page' => $perpage,
                        'count' => $count,
                    ],
                    "result" => $newData,
                    "sort" => $sort,

                ],
                'msg' => '',
            ]);
    }


    public function actionStreamerUserList()
    {
        $models = LiveRoom::find()->select(['streamer_id'])->distinct()->all();
        $newData = [];
        foreach ($models as $model) {
            $array['streamer_id'] = $model['streamer_id'];
            $array['name'] = $model->streamerProfile ? $model->streamerProfile->full_name : null;
            $newData[] = $array;

        }
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $newData,
                'msg' => '',
            ]);


    }

    public function actionDeleteRoom($id)
    {
        $liveRoom = LiveRoom::findOne($id);
        if ($liveRoom->status == 1)
            return Yii::createObject(
                [
                    'class' => RestResult::class,
                    'code' => Consts::REST_MODEL_ERROR,
                    'data' => $id,
                    'msg' => 'The meeting cannot be deleted until it is over',
                ]);
        LiveRoom::deleteRoom($id);
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $id,
                'msg' => '',
            ]);

    }

    public function actionInviteWatcherCancel()
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_SERVER_ERROR,
            ]);

        $invited_cancel_user_ids = Yii::$app->request->post('invited_user_ids');
        $room_id = Yii::$app->request->post("room_id");
        $invite_user_wamp_session_id = Yii::$app->request->post('invite_user_wamp_session_id');
        if (!isset($invited_cancel_user_ids) || !isset($room_id) || !isset($invite_user_wamp_session_id))
            return $result;

        $room = LiveRoom::findOne(['id' => $room_id, 'status' => 1]);
        if (!isset($room))
            return $result;
        $invite_user = Online::findOne(
            [
                'wamp_session_id' => $invite_user_wamp_session_id,
                'user_id' => Yii::$app->user->id,
            ]
        );
        if (!isset($invite_user))
            return $result;

        $invited_cancel_user_ids_int = [];
        foreach ($invited_cancel_user_ids as $called__cancel_user_id) {
            $invited_cancel_user_ids_int[] = intval($called__cancel_user_id);
        }
        if (!LivePushController::pushInvitedCanceled($invited_cancel_user_ids_int, Yii::$app->user->id, $room_id, $invite_user_wamp_session_id))
            return $result;
        $result->code = Consts::REST_OK;
        return $result;
    }

    public function actionGetOnlineUsers()
    {

        $data = [];
        foreach (Online::getOnlineUsers() as $online) {
            $data[] = [
                "id" => $online->id,
                "user_id" => $online->user_id,
                "user_name" => $online->user->username,
                'full_name' => $online->profile->full_name,
                'avatar' => $online->profile->getAvatarUrl(),
                'client_type' => $online->client_type,
                'role' => Yii::$app->user->identity->getAssignmentsStr($online->user_id),
                'long' => $online->location ? $online->location->longitude : null,
                'lat' => $online->location ? $online->location->latitude : null
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

    public function actionLiveRoomStatistics()
    {
        $userLastLocation = UserLocation::find()->where(['user_id' => Yii::$app->user->id])
            ->andWhere(['<', "id", UserLocation::getLastOnlineLocation(Yii::$app->user->id)['id']])
            ->orderBy('id DESC')
            ->one();
        $map_key = Yii::$app->params['Baidu.Map.Appkey'];
        $cooperationTimeData = RoomUserLog::getTotalLiveMeetingLength(Yii::$app->user->id);
        $newData = [
            "cooperation_count" => $cooperationTimeData['room_count'],
            "cooperation_time" => $cooperationTimeData['cooperation_time'],
            "average_cooperation_time" => $cooperationTimeData['average_cooperation_time'],
            "longitude" => $userLastLocation['longitude'],
            "latitude" => $userLastLocation['latitude'],
            "location" => $userLastLocation ? GeoCodingHelper::getAddressComponent($map_key, $userLastLocation['longitude'], $userLastLocation['latitude'], GeoCodingHelper::NO_POIS)['result']['addressComponent']['city'] : null,
        ];
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => $newData,
                'msg' => '',
            ]);
    }


    public function actionOnlineRoomChart()
    {
        return Yii::createObject(
            [
                'class' => RestResult::class,
                'code' => Consts::REST_OK,
                'data' => LiveRoomUser::getRoomOnlineUser(),
                'msg' => "",
            ]);

    }


}