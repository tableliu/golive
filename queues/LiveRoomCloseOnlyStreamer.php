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

class LiveRoomCloseOnlyStreamer extends \yii\base\BaseObject implements JobInterface
{
    public $project_id;
    public $room_id;
    public $reason;

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
        $streamer = LiveRoomUser::findOne([
            'room_id' => $this->room_id,
            'status' => 0,
            'user_id' => $room->streamer_id]);

        if (!empty($streamer))
            return;

        $room_user_count = LiveRoomUser::find()
            ->where([
                'room_id' => $this->room_id,
                'status' => 1])
            ->count();

        if ($room_user_count > 1)
            return;

        $room->status = 0;
        if (!$room->save(false, ['status']))
            return;
        $room_user = LiveRoomUser::findOne(['room_id' => $this->room_id, 'status' => 1]);
        $room_user->status = 0;
        $room_user->save(false, ['status']);
        RoomUserLog::saveRoomUserLog($this->room_id, $room_user->user_id, RoomUserLog::ROOM_USER_LEAVE_ACTION);
        if (LiveRoom::closeRoom($this->room_id, $this->reason)) {
            Yii::info("LiveRoomCloseAfterStreamerLeave room_id:" . $this->room_id);
        }
    }
}