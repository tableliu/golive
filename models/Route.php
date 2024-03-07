<?php

namespace app\models;

use app\components\Settings;
use mdm\admin\components\Configs;
use mdm\admin\components\Helper;
use Yii;
use yii\db\Query;
use yii\mongodb\rbac\Permission;
use yii\mongodb\rbac\Role;
use yii\rbac\DbManager;
use yii\rbac\Item;


/**
 * Description of Route
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Route extends \mdm\admin\models\Route
{

    private $_routePrefix;
    public $itemTable = '{{%auth_item}}';

    public function addNew($routes, $pid = null)
    {
        $manager = Configs::authManager();
        $manager->db = Settings::getProjectDB($pid);
        foreach ($routes as $route) {
            $auth_item = (new Query())
                ->from($this->itemTable)
                ->where(['name' => $route])
                ->one();
            if ($auth_item)
                continue;
            try {
                $r = explode('&', $route);
                $item = $manager->createPermission($this->getPermissionName($route));
                if (count($r) > 1) {
                    $action = '/' . trim($r[0], '/');
                    if (($itemAction = $manager->getPermission($action)) === null) {
                        $itemAction = $manager->createPermission($action);
                        $manager->add($itemAction);
                    }
                    unset($r[0]);
                    foreach ($r as $part) {
                        $part = explode('=', $part);
                        $item->data['params'][$part[0]] = isset($part[1]) ? $part[1] : '';
                    }
                    $this->setDefaultRule();
                    $item->ruleName = RouteRule::RULE_NAME;
                    $manager->add($item);
                    $manager->addChild($item, $itemAction);
                } else {
                    $manager->add($item);
                }
            } catch (Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();
    }

    public function remove($routes, $pid = null)
    {
        $manager = Configs::authManager();
        $manager->db = Settings::getProjectDB($pid);
        foreach ($routes as $route) {
            try {
                $item = $manager->createPermission($this->getPermissionName($route));
                $manager->remove($item);
            } catch (Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();
    }

    public function getRoutes($pid = null)
    {
        $advanced = Configs::instance()->advanced;
        if ($advanced) {
            // Use advanced route scheme.
            // Set advanced route prefix.
            $this->_routePrefix = self::PREFIX_ADVANCED;
            // Create empty routes array.
            $routes = [];
            // Save original app.
            $yiiApp = Yii::$app;
            // Step through each configured application
            foreach ($advanced as $id => $configPaths) {
                // Force correct id string.
                $id = $this->routePrefix . ltrim(trim($id), $this->routePrefix);
                // Create empty config array.
                $config = [];
                // Assemble configuration for current app.
                foreach ($configPaths as $configPath) {
                    // Merge every new configuration with the old config array.
                    $config = yii\helpers\ArrayHelper::merge($config, require(Yii::getAlias($configPath)));
                }
                // Create new app using the config array.
                unset($config['bootstrap']);
                $app = new yii\web\Application($config);
                // Get all the routes of the newly created app.
                $r = $this->getAppRoutes($app);
                // Dump new app
                unset($app);
                // Prepend the app id to all routes.
                foreach ($r as $route) {
                    $routes[$id . $route] = $id . $route;
                }
            }
            // Switch back to original app.
            Yii::$app = $yiiApp;
            unset($yiiApp);
        } else {
            // Use basic route scheme.
            // Set basic route prefix
            $this->_routePrefix = self::PREFIX_BASIC;
            // Get basic app routes.
            $routes = $this->getAppRoutes();
        }

        $exists = [];
        // Yii::$app->authManager->getPermissions();
        foreach (array_keys($this->getItems(2, $pid)) as $name) {
            if ($name[0] !== $this->routePrefix) {
                continue;
            }
            $exists[] = $name;
            unset($routes[$name]);
        }
        return [
            'available' => array_keys($routes),
            'assigned' => $exists,
        ];
    }

    protected function getItems($type, $pid)
    {
        $query = (new Query())
            ->from($this->itemTable)
            ->where(['type' => $type]);

        $items = [];
        foreach ($query->all(Settings::getProjectDB($pid)) as $row) {
            $items[$row['name']] = $this->populateItem($row);
        }

        return $items;
    }

    protected function populateItem($row)
    {
        $class = $row['type'] == Item::TYPE_PERMISSION ? Permission::className() : Role::className();

        if (!isset($row['data']) || ($data = @unserialize(is_resource($row['data']) ? stream_get_contents($row['data']) : $row['data'])) === false) {
            $data = null;
        }

        return new $class([
            'name' => $row['name'],
            'type' => $row['type'],
            'description' => $row['description'],
            'ruleName' => $row['rule_name'] ?: null,
            'data' => $data,
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ]);
    }


}
