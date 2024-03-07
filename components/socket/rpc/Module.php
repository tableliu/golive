<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/20
 * Time: 0:30
 */

namespace app\components\socket\rpc;

use Yii;
use yii\base\InvalidConfigException;

class Module extends \yii\base\Module
{
    public $rpcControllerNamespace;

    public function init()
    {
        if ($this->controllerNamespace === null) {
            $class = get_class($this);
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }

        if ($this->rpcControllerNamespace === null) {
            $class = get_class($this);
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->rpcControllerNamespace = substr($class, 0, $pos) . '\\controllers' . '\\rpc';
            }
        }
    }

    public function createRpcController($route)
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

        $controller = $this->createRpcControllerByID($id);
        if ($controller === null && $route !== '') {
            $controller = $this->createRpcControllerByID($id . '/' . $route);
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

    public function createRpcControllerByID($id)
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
        $className = ltrim($this->rpcControllerNamespace . '\\' . str_replace('/', '\\', $prefix) . $className, '\\');
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
}