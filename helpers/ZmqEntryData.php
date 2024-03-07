<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/2/26
 * Time: 16:11
 */

namespace app\helpers;


use app\components\socket\Pusher;
use yii\base\BaseObject;

/*
 * @property string $topic
 * @property array $blacklist
 * @property array $whitelist
 * @property string $msg
 */
class ZmqEntryData extends BaseObject
{
    public $topic = Pusher::TOPIC_IIP_BASE;
    /*
     * $client->WAMP->sessionId
     */
    public $blacklist;
    /*
     * $client->WAMP->sessionId
     */
    public $whitelist;
    public $msg;
}