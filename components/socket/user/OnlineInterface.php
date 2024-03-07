<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/28
 * Time: 23:04
 */

namespace app\components\socket\user;


interface OnlineInterface
{
    public function online();

    public function offline();

}