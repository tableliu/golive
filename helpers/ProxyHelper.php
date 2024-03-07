<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2019/8/23
 * Time: 20:44
 */

namespace app\helpers;


use app\helpers\muz\MuzHelperInterface;
use app\models\Proxy;
use Yii;

class ProxyHelper
{
    const MY_LOG_CATEGORY = 'app\helpers';

    public static function getProxy()
    {
        return null;
    }

    public static function fetchAndUpdateProxy($time = 101, $deal_old_proxy = 0)
    {
    }
}