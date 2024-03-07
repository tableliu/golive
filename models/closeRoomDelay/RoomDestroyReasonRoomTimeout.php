<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/11
 * Time: 11:12
 */

namespace app\models\closeRoomDelay;


use app\queues\LiveRoomCloseTimeOut;
use Yii;

class RoomDestroyReasonRoomTimeout implements InterfaceCloseRoomDelay
{
    /**
     * @param $room_id
     * @param $reason
     * @param $delay_sec
     * @param $pid
     * @return mixed|void
     */
    public function closeRoomDelay($room_id, $reason, $delay_sec, $pid)
    {
        Yii::$app->queueCloseRoomDelay
            ->delay($delay_sec)
            ->push(new LiveRoomCloseTimeOut(['room_id' => $room_id, 'reason' => $reason, 'project_id' => $pid]));

    }

}