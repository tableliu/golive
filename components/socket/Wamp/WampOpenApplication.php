<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/30
 * Time: 15:43
 */

namespace app\components\socket\Wamp;


use app\components\socket\Session\Session;
use app\components\socket\user\UserFactory;
use Yii;
use yii\base\Application;
use yii\base\Component;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\web\User;

class WampOpenApplication extends Application
{
    public function preInit(&$config)
    {
        if (isset($config['conn'])) {
            $config['components']['connection'] = $config['conn'];
            $config['components']['user'] = UserFactory::create($config['conn']);
            unset($config['conn']);
        } else {
            throw new InvalidConfigException('The "conn" configuration for the Application is required.');
        }

        parent::preInit($config);
    }

    public function run()
    {
        try {
            $exitStatus = $this->handleRequest(null);
            return $exitStatus;
        } catch (\Exception $e) {
            $this->end(1);
            return 1;
        }
    }

    public function end($status = 0, $response = null)
    {
        if (YII_ENV_TEST) {
            throw new ExitException($status);
        }
//        exit($status);
    }

    public function getDb()
    {
        return $this->settings->getCurrentDB();
    }

    public function handleRequest($request)
    {
        // must login
        if ($this->user->online() === false) {
            printf("User " . $this->user->id . " Open Failed \r\n");
            $this->get('connection')->close();
            return 1;
        } else {
            printf("User " . $this->user->id . " Open Success \r\n");
            return 0;
        }

    }

    /**
     * Returns the user component.
     * @return User the user component.
     * @throws InvalidConfigException
     */
    public function getUser()
    {
        return $this->get('user');
    }

    public function getConnection()
    {
        return $this->get('connection');
    }

    /**
     * Returns the session component.
     * @return Session the session component.
     */
    public function getSession()
    {
        return isset($this->connection) ? $this->connection->Session : null;
    }
}