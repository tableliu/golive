<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/11
 * Time: 11:12
 */

namespace app\models\closeRoomDelay;


interface InterfaceCloseRoomDelay
{
    /**
     * @param $room_id
     * @param $reason
     * @param $delay_sec
     * @param $pid
     * @return mixed
     */
    public function closeRoomDelay($room_id, $reason, $delay_sec, $pid);

}