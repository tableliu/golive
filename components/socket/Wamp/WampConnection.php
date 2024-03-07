<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/1/16
 * Time: 22:31
 */

namespace app\components\socket\Wamp;


use Ratchet\ConnectionInterface;
use Yii;
use Ratchet\Wamp\ServerProtocol as WAMP;

class WampConnection extends \Ratchet\Wamp\WampConnection
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ConnectionInterface $conn)
    {
        parent::__construct($conn);

//        $this->WAMP = new \StdClass;
//        if (isset(Yii::$app->session->id))
//            $this->WAMP->sessionId = Yii::$app->session->id;
//        else
//            $this->WAMP->sessionId = str_replace('.', '', uniqid(mt_rand(), true));
//        $this->WAMP->prefixes = array();
//
//        $this->getConnection()->send(json_encode(array(WAMP::MSG_WELCOME, $this->WAMP->sessionId, 1, \Ratchet\VERSION)));
    }
}