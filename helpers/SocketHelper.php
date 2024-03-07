<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/2/26
 * Time: 15:56
 */

namespace app\helpers;


use Yii;
use yii\helpers\Json;
use ZMQ;
use ZMQContext;

class SocketHelper
{
    /**
     * @param ZmqEntryData $entryData
     * @return \ZMQSocket|bool
     */
    public static function pushMsg($entryData)
    {
        // whitelist required
        if ($entryData instanceof ZmqEntryData) {
            if (!isset($entryData->whitelist) || empty($entryData->whitelist))
                return false;
        } else {
            return false;
        }
        try {
            $context = new ZMQContext();
            $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'main_pusher');
            $socket->connect(Yii::$app->params['ZMQ_SOCKET_DSN']);
            return $socket->send(JSON::encode($entryData));
        } catch (\ZMQSocketException $e) {
            Yii::error($e->getMessage());
            return false;
        }
    }

    /**
     * @param ZmqEntryData $entryData
     * @return \ZMQSocket|bool
     */
    public static function broadcastMsg($entryData)
    {
        try {
            $context = new ZMQContext();
            $socket = $context->getSocket(ZMQ::SOCKET_PUSH, 'main_pusher');
            $socket->connect(Yii::$app->params['ZMQ_SOCKET_DSN']);
            return $socket->send(JSON::encode($entryData));
        } catch (\ZMQSocketException $e) {
            Yii::error($e->getMessage());
            return false;
        }
    }


}