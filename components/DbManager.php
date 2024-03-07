<?php

namespace app\components;

use app\models\AuthItem;
use app\models\Project;
use app\modules\easyforms\models\Setting;
use Yii;
use yii\caching\Cache;
use yii\db\Query;
use yii\di\Instance;
use yii\helpers\Json;
use yii\rbac\Assignment;
use yii\rbac\Item;

class DbManager extends \yii\rbac\DbManager
{
    public $assignmentTable = '{{%auth_assignment}}';
    private $_checkAccessAssignments = [];

    /**
     * Initializes the application component.
     * This method overrides the parent implementation by establishing the database connection.
     */
    public function init()
    {
        $this->db = Yii::$app->getDb();
        if ($this->cache !== null) {
            $this->cache = Instance::ensure($this->cache, Cache::className());
        }
    }

    public function assignRoles ($pid, $rolename, $userId, $order)
    {
        $role = new AuthItem();
        $role->name = $rolename;
        $assignment = new Assignment([
            'userId' => $userId,
            'roleName' => $role->name,
            'createdAt' => time(),
        ]);
        Settings::getProjectDB($pid)->createCommand()
            ->insert($this->assignmentTable, [
                'user_id' => $assignment->userId,
                'item_name' => $assignment->roleName,
                'order' => $order,
                'created_at' => $assignment->createdAt,
            ])->execute();
        unset($this->_checkAccessAssignments[(string)$userId]);
        return $assignment;
    }

    //获取当前用户角色
    public function getAssignRoles ($pid, $userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return [];
        }
        $query = (new Query())
            ->from($this->assignmentTable)
            ->where(['user_id' => (string)$userId]);

        $assignments = "";
        foreach ($query->all(Settings::getProjectDB($pid)) as $row) {
            $assignments .= $row['item_name'] . ",";

        }
        return substr($assignments, 0, -1);

    }

    public function deleteAssignRoles ($pid, $userId)
    {
        if ($this->isEmptyUserId($userId)) {
            return false;
        }

        unset($this->_checkAccessAssignments[(string)$userId]);
        return Settings::getProjectDB($pid)->createCommand()
                ->delete($this->assignmentTable, ['user_id' => (string)$userId])
                ->execute() > 0;

    }


    public function getPermissionsRole ($pid)
    {
        $result = [];
        $query = (new Query())->from($this->itemTable)->where([
            'type' => Item::TYPE_ROLE,
        ]);
        foreach ($query->all(Settings::getProjectDB($pid)) as $row) {
            $result[] = $row['name'];
        }
        return $result;
    }

    public function addUserRole ($role, $userId)
    {
        $assignment = new Assignment([
            'userId' => $userId,
            'roleName' => $role->name,
            'createdAt' => time(),
        ]);

        Yii::$app->getDb()->createCommand()
            ->insert($this->assignmentTable, [
                'user_id' => $assignment->userId,
                'item_name' => $assignment->roleName,
                'created_at' => $assignment->createdAt,
                'order' => $role->order

            ])->execute();

        unset($this->_checkAccessAssignments[(string)$userId]);
        return $assignment;
    }


}
