<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/11
 * Time: 11:12
 */

namespace app\components;


interface SettingsInterface
{
    public function getUserDB();

    public function getCurrentDB($cache_key);
}