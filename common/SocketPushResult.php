<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/28
 * Time: 13:27
 */

namespace app\common;


use yii\base\BaseObject;

class SocketPushResult extends BaseObject
{
    public $code = 1;
    public $uri = "";
    public $data = null;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
    }
}