<?php

namespace app\models;

use app\components\Settings;
use mdm\admin\components\Configs;
use mdm\admin\components\Helper;
use Yii;

/**
 * Description of Assignment
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 2.5
 */
class Assignment extends \mdm\admin\models\Assignment
{
    public $id;

    public $user;

    public function __construct($id, $user = null, $config = array())
    {
        $this->id = $id;
        $this->user = $user;

    }

    public function assign($items, $pid = null)
    {
        $manager = Configs::authManager();
        $pid ? $manager->db = Settings::getProjectDB($pid) : "";
        $success = 0;
        foreach ($items as $name) {
            try {
                $item = $manager->getRole($name);
                $item = $item ?: $manager->getPermission($name);
                $manager->assign($item, $this->id);
                $success++;
            } catch (\Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();
        return $success;
    }


    public function revoke($items, $pid = null)
    {
        $manager = Configs::authManager();
        $pid ? $manager->db = Settings::getProjectDB($pid) : "";
        $success = 0;
        foreach ($items as $name) {
            try {
                $item = $manager->getRole($name);
                $item = $item ?: $manager->getPermission($name);
                $manager->revoke($item, $this->id);
                $success++;
            } catch (\Exception $exc) {
                Yii::error($exc->getMessage(), __METHOD__);
            }
        }
        Helper::invalidate();
        return $success;
    }


    public function getItems($pid = null)
    {
        $manager = Configs::authManager();
        $pid ? $manager->db = Settings::getProjectDB($pid) : "";
        $available = [];
        foreach (array_keys($manager->getRoles()) as $name) {
            $available[$name] = 'role';
        }

        foreach (array_keys($manager->getPermissions()) as $name) {
            if ($name[0] != '/') {
                $available[$name] = 'permission';
            }
        }
        $assigned = [];

        foreach ($manager->getAssignments($this->id) as $item) {
            $assigned[$item->roleName] = $available[$item->roleName];
            unset($available[$item->roleName]);
        }

        return [
            'available' => $available,
            'assigned' => $assigned,
        ];
    }


}
