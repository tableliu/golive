<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/1/16
 * Time: 22:26
 */

namespace app\components\socket\Wamp;

use Ratchet\Wamp\TopicManager;
use Ratchet\Wamp\WampServerInterface;

class WampServer extends \Ratchet\Wamp\WampServer
{
    public function __construct(WampServerInterface $app)
    {
        parent::__construct($app);
//        $this->wampProtocol = new ServerProtocol(new TopicManager($app));
    }
}