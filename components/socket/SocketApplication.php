<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/1/7
 * Time: 19:01
 */

namespace app\components\socket;

use Ratchet\ConnectionInterface;
use Yii;
use yii\base\Component;

/**
 * Class SocketApplication
 * @package app\components\socket
 * @property ConnectionInterface connection
 */
class SocketApplication extends \yii\console\Application
{

    public function __construct($config = [])
    {
        $config = $this->loadConfig($config);

        Yii::$app = $this;
        static::setInstance($this);

        $this->state = self::STATE_BEGIN;

        $this->preInit($config);

        $this->registerErrorHandler($config);

        Component::__construct($config);
    }

//    public function getSession()
//    {
//        return $this->get('session');
//    }
//
//    public function setSession($session)
//    {
//        return $this->set('session', $session);
//    }
//
//    public function getUser()
//    {
//        return $this->get('user');
//    }
//
//    public function setUser($user)
//    {
//        return $this->set('user', $user);
//    }
//
//    public function getConnection()
//    {
//        return $this->get('connection');
//    }
//
//    public function setConnection($connection)
//    {
//        return $this->set('connection', $connection);
//    }

    public function getDb()
    {
        return $this->settings->getCurrentDB();
    }
}