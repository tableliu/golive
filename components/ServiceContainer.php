<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/7/4
 * Time: 16:07
 */

namespace app\components;

use Yii;
use yii\base\Component;
use yii\di\ServiceLocator;

class ServiceContainer extends Component
{
    /**
     * @var ServiceLocator
     */
    private $_serviceLocator;

    public function __construct($config = [])
    {
        $this->_serviceLocator = new ServiceLocator();
        parent::__construct($config);
    }

    public function __get($id)
    {
        if (isset($this->getComponents(false)[$id]))
            return $this->getComponents(false)[$id];
        if (isset($this->getComponents(true)[$id])) {
            $definition = $this->getComponents(true)[$id];
            $role = Yii::$app->user->identity->getMajorRole();
            if (isset($definition['components'][$role]))
                $this->_serviceLocator->set($id, $definition['components'][$role]);
        }

        return $this->_serviceLocator->get($id);
    }

    public function set($id, $definition)
    {
        if ($definition != null &&
            is_array($definition) &&
            !isset($definition['__class']) &&
            !isset($definition['class'])) {
            $definition_wrap = null;
            $definition_wrap['class'] = 'yii\di\ServiceLocator';
            $definition_wrap['components'] = $definition;
            $definition = $definition_wrap;
        }

        $this->_serviceLocator->set($id, $definition);
    }

    public function getComponents($returnDefinitions = true)
    {
        return $this->_serviceLocator->getComponents($returnDefinitions);
    }

    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }

}