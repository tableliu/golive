<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/26
 * Time: 22:17
 */

namespace app\helpers;


class DebugHelper
{
    static function memory_usage()
    {
        $memory = (!function_exists('memory_get_usage')) ? '0' : round(memory_get_usage() / 1024 / 1024, 2) . 'MB';
        return $memory;
    }
}