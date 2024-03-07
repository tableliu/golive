<?php

namespace app\controllers;

use app\components\Settings;
use Codeception\Util\PathResolver;
use Yii;
use app\common\IIPActiveController;
use app\common\RestResult;
use app\common\Consts;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use app\models\Menu;
use yii\caching\TagDependency;
use mdm\admin\components\Configs;
use yii\helpers\ArrayHelper;


/**
 * ReadyController implements the CRUD actions for Ready model.
 */
class TopMenuController extends IIPActiveController
{
    public $userClassName;
    public $modelClass = 'app\models\Menu';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
                'delete' => ['post'],
                'update' => ['post'],
            ],
        ];
        return $behaviors;
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    //get menu_index
    public function actionIndex()
    {
        $root = null;
        $refresh = false;
        $callback = function ($menu) {
            return [
                'id' => $menu['id'],
                'title' => $menu['name'],
                'path' => isset($menu['route']) ? $menu['route'] : "",
                'items' => $menu['children'],
                'icon' => isset($menu['data']) ? $menu['data'] : "list",
                'fontend_path' => isset($menu['fontend_path']) ? $menu['fontend_path'] : "",
            ];

        };
        $userId = Yii::$app->user->id;
        $config = Configs::instance();

        /* @var $manager \yii\rbac\BaseManager */
        $manager = Configs::authManager();
        $menus = Menu::find()->asArray()->indexBy('id')->all();
        $key = [__METHOD__, $userId, $manager->defaultRoles, Settings::getCurrentPid()];
        $cache = $config->cache;
        if ($refresh || $cache === null || ($assigned = $cache->get($key)) === false) {
            $routes = $filter1 = $filter2 = [];
            if ($userId !== null) {
                foreach ($manager->getPermissionsByUser($userId) as $name => $value) {
                    if ($name[0] === '/') {
                        if (substr($name, -2) === '/*') {
                            $name = substr($name, 0, -1);
                        }
                        $routes[] = $name;
                    }
                }
            }
            foreach ($manager->defaultRoles as $role) {
                foreach ($manager->getPermissionsByRole($role) as $name => $value) {
                    if ($name[0] === '/') {
                        if (substr($name, -2) === '/*') {
                            $name = substr($name, 0, -1);
                        }
                        $routes[] = $name;
                    }
                }
            }
            $routes = array_unique($routes);
            sort($routes);
            $prefix = '\\';
            foreach ($routes as $route) {
                if (strpos($route, $prefix) !== 0) {
                    if (substr($route, -1) === '/') {
                        $prefix = $route;
                        $filter1[] = $route . '%';
                    } else {
                        $filter2[] = $route;
                    }
                }
            }
            $assigned = [];
            $query = Menu::find()->select(['id'])->asArray();
            if (count($filter2)) {
                $assigned = $query->where(['route' => $filter2])->column();
            }
            if (count($filter1)) {
                $query->where('route like :filter');
                foreach ($filter1 as $filter) {
                    $assigned = array_merge($assigned, $query->params([':filter' => $filter])->column());
                }
            }
            $assigned = static::requiredParent($assigned, $menus);
            if ($cache !== null) {
                $cache->set($key, $assigned, $config->cacheDuration, new TagDependency([
                    'tags' => Configs::CACHE_TAG
                ]));
            }
        }
        $key = [__METHOD__, $assigned, $root];
        if ($refresh || $callback !== null || $cache === null || (($result = $cache->get($key)) === false)) {
            $result = static::normalizeMenu($assigned, $menus, $callback, $root);
            if ($cache !== null && $callback === null) {
                $cache->set($key, $result, $config->cacheDuration, new TagDependency([
                    'tags' => Configs::CACHE_TAG
                ]));
            }
        }
        $authManager = Yii::$app->getAuthManager();
        if ($authManager instanceof \yii\rbac\ManagerInterface) {
            $roles = ArrayHelper::toArray($authManager->getRolesByUser($userId));
            $name = [];
            if ($roles) {
                foreach ($roles as $key => $value) {
                    $name[] = $roles[$key]['name'];
                }
            } else {
                $name = array();
            }

        }
        $result = [
            "datamenu" => $result,
            "assignments" => $name
        ];
        return $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_OK,
                'data' => $result,
                'msg' => Yii::t('app', 'successfully'),
            ]);;
    }

    private static function requiredParent($assigned, &$menus)
    {
        $l = count($assigned);
        for ($i = 0; $i < $l; $i++) {
            $id = $assigned[$i];
            $parent_id = $menus[$id]['parent'];
            if ($parent_id !== null && !in_array($parent_id, $assigned)) {
                $assigned[$l++] = $parent_id;
            }
        }

        return $assigned;
    }

    private static function normalizeMenu(&$assigned, &$menus, $callback, $parent = null)
    {
        $result = [];
        $order = [];
        foreach ($assigned as $id) {
            $menu = $menus[$id];
            if ($menu['parent'] == $parent) {
                $menu['children'] = static::normalizeMenu($assigned, $menus, $callback, $id);

                if ($callback !== null) {
                    $item = call_user_func($callback, $menu);
                } else {
                    $item = [
                        'label' => Yii::t('rbac-admin', $menu['name']),
                        'url' => static::parseRoute($menu['route']),
                    ];
                    if ($menu['children'] != []) {
                        $item['items'] = $menu['children'];
                    }
                }
                $result[] = $item;
                $order[] = $menu['order'];
            }
        }
        if ($result != []) {
            array_multisort($order, $result);
        }

        return $result;
    }

}
