<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/9/25
 * Time: 18:06
 */

namespace app\queues;


use app\models\LiveRoom;
use app\models\LiveRoomUser;
use app\models\RoomUserLog;
use Yii;
use yii\queue\JobInterface;
use yii\queue\Queue;

class LiveRoomCloseAfterStreamerLeave extends \yii\base\BaseObject implements JobInterface
{
    public $project_id;
    public $room_id;
    public $reason;
    public $leave_log_id;

    /**
     * @param Queue $queue which pushed and is handling the job
     * @return void|mixed result of the job execution
     */
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

        // 回来了
        $streamer = LiveRoomUser::findOne([
            'room_id' => $this->room_id,
            'status' => 1,
            'user_id' => $room->streamer_id]);
        if (!empty($streamer))
            return;

        // 回来过又离开了，可以用 $leave_log_id 判断。这里不做重新计时。

        // 先锁定，防止新用户进入
        $room->status = 0;
        if (!$room->save(false, ['status']))
            return;

        $room_users = LiveRoomUser::findAll(['room_id' => $this->room_id, 'status' => 1]);
        foreach ($room_users as $room_user) {
            $room_user->status = 0;
            $room_user->save(false, ['status']);
            RoomUserLog::saveRoomUserLog($this->room_id, $room_user->user_id, RoomUserLog::ROOM_USER_LEAVE_ACTION);
        }
        if (LiveRoom::closeRoom($this->room_id, $this->reason)) {
            Yii::info("LiveRoomCloseAfterStreamerLeave room_id:" . $this->room_id);
        }
    }
}