<?php

namespace app\models;

use app\components\Settings;
use Yii;
use yii\data\ArrayDataProvider;
use mdm\admin\components\Configs;
use yii\data\Pagination;
use yii\rbac\Item;
use yii\db\Query;
use app\common\RestModels;
use yii\rbac\Permission;
use yii\rbac\Role;


/**
 * AuthItemSearch represents the model behind the search form about AuthItem.
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class AuthItemSearch extends RestModels
{
    const TYPE_ROUTE = 101;

    public $name;
    public $type;
    public $description;
    public $ruleName;
    public $data;
    public $itemTable = '{{%auth_item}}';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'ruleName', 'description'], 'safe'],
            [['type'], 'integer'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => Yii::t('rbac-admin', 'Name'),
            'item_name' => Yii::t('rbac-admin', 'Name'),
            'type' => Yii::t('rbac-admin', 'Type'),
            'description' => Yii::t('rbac-admin', 'Description'),
            'ruleName' => Yii::t('rbac-admin', 'Rule Name'),
            'data' => Yii::t('rbac-admin', 'Data'),
        ];
    }


    /**
     * Search authitem
     * @param array $params
     * @param null $pid
     * @return \yii\data\ActiveDataProvider|\yii\data\ArrayDataProvider
     */
    public function search($params, $pid = null)
    {
        if (array_key_exists("sort", Yii::$app->request->getQueryParams())) {
            if (strpos(Yii::$app->request->getQueryParams()['sort'], 'ruleName') !== false) {
                $sortname = "rule_name";
                strpos(Yii::$app->request->getQueryParams()['sort'], '-') !== false ? $sort = $sortname . " DESC" : $sort = $sortname . " ASC";
            } else {
                strpos(Yii::$app->request->getQueryParams()['sort'], '-') !== false ? $sort = substr(Yii::$app->request->getQueryParams()['sort'] . " DESC", 1) : $sort = Yii::$app->request->getQueryParams()['sort'] . " ASC";
            }

        } else {
            $sort = "name DESC";
        }
        $this->type ? $where = ['type' => $this->type] : $where = "";
        $query = (new Query())
            ->from($this->itemTable)
            ->where($where)
            ->orderBy($sort)
            ->all($pid ? Settings::getProjectDB($pid) : null);
        $items = [];
        foreach ($query as $row) {
            $items[$row['name']] = $this->populateItem($row);
        }
        if ($this->type == Item::TYPE_ROLE) {
            $items = $items;

        } elseif ($this->type == Item::TYPE_PERMISSION) {
            $items = array_filter($items, function ($item) {
                return $this->type == Item::TYPE_PERMISSION xor strncmp($item->name, '/', 1) === 0;
            });
        } else {
            $items = $items;
        }


        $dataProvider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => [
                'pageSize' => (isset($params['iipsearch']) && !empty($params['iipsearch'])) ? '' : (isset($params['per-page']) ? $params['per-page'] : Yii::$app->params['GridView.pagination.pageSize']),
            ],

        ]);
        $this->load($params);
        if ($this->validate()) {
            $search = mb_strtolower(trim($this->name));
            $desc = mb_strtolower(trim($this->description));
            $ruleName = $this->ruleName;
            foreach ($items as $name => $item) {
                $f = (empty($search) || mb_strpos(mb_strtolower($item->name), $search) !== false) &&
                    (empty($desc) || mb_strpos(mb_strtolower($item->description), $desc) !== false) &&
                    (empty($ruleName) || $item->ruleName == $ruleName);
                if (!$f) {
                    unset($items[$name]);
                }
            }
        }
        return $dataProvider;
    }

    public function assign($params, $pid)
    {
        $sort = "";
        if (array_key_exists("sort", Yii::$app->request->getQueryParams())) {
            if (strpos(Yii::$app->request->getQueryParams()['sort'], 'ruleName') !== false) {
                $sortname = "rule_name";
                strpos(Yii::$app->request->getQueryParams()['sort'], '-') !== false ? $sort = $sortname . " DESC" : $sort = $sortname . " ASC";
            }
        } else {
            $sort = "name DESC";
        }

        if ($this->type == 2) {
            $where = ['type' => $this->type];
        } else {
            $where = "";
        }
        $query = (new Query())
            ->from($this->itemTable)
            ->where($where)
            ->orderBy($sort)
            ->all(Settings::getProjectDB($pid));
        foreach ($query as $row) {
            $items[$row['name']] = $this->populateItem($row);
        }

        $dataProvider = new ArrayDataProvider([
            'allModels' => $items,
            'pagination' => [
                'pageSize' => (isset($params['iipsearch']) && !empty($params['iipsearch'])) ? '' : (isset($params['per-page']) ? $params['per-page'] : Yii::$app->params['GridView.pagination.pageSize']),
            ],

        ]);

        $this->load($params);
        if ($this->validate()) {
            $search = mb_strtolower(trim($this->name));
            $desc = mb_strtolower(trim($this->description));
            $ruleName = $this->ruleName;
            foreach ($items as $name => $item) {
                $f = (empty($search) || mb_strpos(mb_strtolower($item->name), $search) !== false) &&
                    (empty($desc) || mb_strpos(mb_strtolower($item->description), $desc) !== false) &&
                    (empty($ruleName) || $item->ruleName == $ruleName);
                if (!$f) {
                    unset($items[$name]);
                }
            }
        }
        return $dataProvider;
    }

    public function populateItem($row)
    {
        $class = $row['type'] == Item::TYPE_PERMISSION ? Permission::className() : Role::className();

        if (!isset($row['data']) || ($data = @unserialize(is_resource($row['data']) ? stream_get_contents($row['data']) : $row['data'])) === false) {
            $data = null;
        }

        return new $class([
            'name' => $row['name'],
            'type' => $row['type'],
            'description' => $row['description'],
            'ruleName' => $row['rule_name'],
            'data' => $data,
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ]);
    }


}
