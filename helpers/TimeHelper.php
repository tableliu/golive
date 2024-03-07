<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/19
 * Time: 17:22
 */

namespace app\helpers;

class TimeHelper
{

    public static function timeDiff($timediff)
    {
        $days = intval($timediff / 86400);

        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);

        $remain = $remain % 3600;
        $mins = intval($remain / 60);

        $secs = $remain % 60;
        return sprintf("%02d", $hours) . ":" . sprintf("%02d", $mins) . ":" . sprintf("%02d", $secs);
    }

}