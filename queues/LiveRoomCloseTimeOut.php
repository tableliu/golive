<?php


namespace app\queues;


use app\models\LiveRoom;
use app\models\LiveRoomUser;
use app\models\RoomUserLog;
use Yii;
use yii\queue\JobInterface;
use yii\queue\Queue;

class LiveRoomCloseTimeOut extends \yii\base\BaseObject implements JobInterface
{
    public $project_id;
    public $room_id;
    public $reason;


    public function execute($queue)
    {
        $db = Yii::$app->settings->getProjectsDB([$this->project_id])[0];
        Yii::$app->settings->setCurrentDB($db);
        $room = LiveRoom::findOne([
            'id' => $this->room_id,
            'status' => 1
        ]);
        if (empty($room))
            return;
        $room->status = 0;
        if (!$room->save(false, ['status']))
            return;
        $live_room_users = LiveRoomUser::findAll(['room_id' => $this->room_id, 'status' => 1]);
        LiveRoomUser::updateAll(['status' => 0], ['room_id' => $this->room_id]);
        foreach ($live_room_users as $live_room_user) {
            RoomUserLog::saveRoomUserLog($this->room_id, $live_room_user->user_id, RoomUserLog::ROOM_USER_LEAVE_ACTION);
        }
        if (LiveRoom::closeRoom($this->room_id, $this->reason)) {
            Yii::info("LIveRoomCloseTimeOut room_id:" . $this->room_id);
        }


    }
}