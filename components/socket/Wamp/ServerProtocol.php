<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/1/16
 * Time: 22:29
 */

namespace app\components\socket\Wamp;


use Ratchet\ConnectionInterface;

class ServerProtocol extends \Ratchet\Wamp\ServerProtocol
{
    public function onOpen(ConnectionInterface $conn)
    {
        $decor = new WampConnection($conn);
        $this->connections->attach($conn, $decor);

        $this->_decorating->onOpen($decor);
    }
}