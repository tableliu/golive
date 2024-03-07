<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/30
 * Time: 15:43
 */

namespace app\components\socket\rpc;


use app\components\socket\Session\Session;
use app\components\socket\user\UserFactory;
use app\components\socket\user\WebUser;
use Yii;
use yii\base\Application;
use yii\base\Component;
use yii\base\Controller;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\web\NotFoundHttpException;

class SocketRpcApplication extends Application
{
    public $controllerNamespace = 'app\\controllers\\rpc';
    private $_call_id;

    public function preInit(&$config)
    {
        if (isset($config['conn'])) {
            $config['components']['connection'] = $config['conn'];
            $config['components']['user'] = UserFactory::create($config['conn']);
            unset($config['conn']);
        } else {
            throw new InvalidConfigException('The "conn" configuration for the Application is required.');
        }

        if (isset($config['call_id'])) {
            $this->_call_id = $config['call_id'];
            unset($config['call_id']);
        } else {
            throw new InvalidConfigException('The "call_id" configuration for the Application is required.');
        }

        if (isset($config['pusher'])) {
            $config['components']['pusher'] = $config['pusher'];
            unset($config['pusher']);
        } else {
            throw new InvalidConfigException('The "pusher" configuration for the Application is required.');
        }

        parent::preInit($config);
    }

    public function run()
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;
        } catch (ExitException $e) {
            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;
        }
    }

    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ?: $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        }
    }

    public function getDb()
    {
        return $this->settings->getCurrentDB();
    }

    public function handleRequest($request)
    {
        list($route, $params, $cid) = $request->resolve();
        try {
            $this->requestedRoute = $route;
            $result = $this->runAction($route, $params);
            if ($result instanceof \app\components\socket\rpc\Response) {
                return $result;
            }

            $response = $this->getResponse();
            if ($result !== null) {
                $response->data = $result;
                $response->cid = $cid;
            }

            return $response;
        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'), $e->getCode(), $e);
        }
    }

    public function runAction($route, $params = [])
    {
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            list($controller, $actionID) = $parts;
            $result = $controller->runAction($actionID, $params);

            return $result;
        }

        $id = $this->getUniqueId();
        throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
    }

    public function createController($route)
    {
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }
        if (strpos($route, 'rpc') === false)
            return false;
        elseif (strpos($route, 'rpc') !== 0) {
            if (strpos($route, '/') !== false) {
                list($id, $route) = explode('/', $route, 2);
            } else {
                $id = $route;
                $route = '';
            }

            $module = $this->getModule($id);
            if ($module !== null) {
                return $module->createRpcController($route);
            }
        } else {
            $route = substr($route, 4); // remove rpc/
            if (strpos($route, '/') !== false) {
                list($id, $route) = explode('/', $route, 2);
            } else {
                $id = $route;
                $route = '';
            }
        }

        // module and controller map take precedence
        if (isset($this->controllerMap[$id])) {
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }

        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }

        $controller = $this->createControllerByID($id);
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }

//        if (isset($controller))
//            $controller->conn = $this->request->_conn;

        return $controller === null ? false : [$controller, $route];
    }

    private function isIncorrectClassNameOrPrefix($className, $prefix)
    {
        if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
            return true;
        }
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return true;
        }

        return false;
    }

    public function createControllerByID($id)
    {
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }

        if ($this->isIncorrectClassNameOrPrefix($className, $prefix)) {
            return null;
        }

        $className = preg_replace_callback('%-([a-z0-9_])%i', function ($matches) {
                return ucfirst($matches[1]);
            }, ucfirst($className)) . 'Controller';
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix) . $className, '\\');
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }

        if (is_subclass_of($className, 'yii\base\Controller')) {
            $controller = Yii::createObject($className, [$id, $this]);
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException('Controller class must extend from \\yii\\base\\Controller.');
        }

        return null;
    }

    /**
     * Returns the request component.
     * @return Request the request component.
     * @throws InvalidConfigException
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Returns the response component.
     * @return Response the response component.
     * @throws InvalidConfigException
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    public function getPusher()
    {
        return $this->get('pusher');
    }

    /**
     * Returns the user component.
     * @return WebUser the user component.
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