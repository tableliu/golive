<?php

namespace app\models;

use app\components\Settings;
use Yii;
use mdm\admin\components\Configs;
use yii\db\Query;
use app\common\IIPActiveRecord;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;

/**
 * This is the model class for table "menu".
 *
 * @property integer $id Menu id(autoincrement)
 * @property string $name Menu name
 * @property integer $parent Menu parent
 * @property string $route Route for this menu
 * @property integer $order Menu order
 * @property string $data Extra information for this menu
 * @property string $fontend_path Menu fontend_path
 * @property Menu $menuParent Menu parent
 * @property Menu[] $menus Menu children
 *
 * @author Misbahul D Munir <misbahuldmunir@gmail.com>
 * @since 1.0
 */
class Menu extends \mdm\admin\models\Menu
{
    private static $pid;


    public static function tableName()
    {
        $language = Settings::getCurrentLanguage();
        $table =  Yii::$app->params['Languages'][$language]['table'];
        return '{{%'.$table.'}}';

    }


    public function rules()
    {
        return [
            [['name'], 'required'],
            [['parent_name'], 'in',
                'range' => static::find()->select(['name'])->column(),
                'message' => 'Menu "{value}" not found.'],
            [['parent', 'route', 'data', 'order'], 'default'],
            [['parent'], 'filterParent', 'when' => function () {
                return !$this->isNewRecord;
            }],
            [['order'], 'integer']
        ];
    }
    public function load($data, $formName = null)
    {

        $scope = $formName === null;
        if (!empty($data)) {
            $this->setAttributes($data);

            return true;
        } elseif (isset($data[$scope])) {
            $this->setAttributes($data[$scope]);

            return true;
        }
    }

    public function getParentName($parent)
    {
        $row = (new Query)->select(['name'])
            ->from(static::tableName())
            ->where('[[id]]=' . $parent)
            ->one();

        return $row['name'];
    }

    public function setPid($pid)
    {
        self::$pid = $pid;
    }


    public function getPid()
    {
        return self::$pid;
    }

    public static function getDb()
    {
        if (self::getPid())
            return Settings::getProjectDB(self::$pid);
        return Yii::$app->getDb();
    }

    private static $_routes;

    public static function getLeftSavedRoutes($pid = null)
    {

        if (self::$_routes === null) {
            self::$_routes = [];
            $auth = Configs::authManager();
            $auth->db = Settings::getProjectDB($pid);
            foreach (Configs::authManager()->getPermissions() as $name => $value) {
                if ($name[0] === '/' && substr($name, -1) != '*') {
                    self::$_routes[] = $name;
                }
            }
        }
        return self::$_routes;
    }


    public function getMenuParent()
    {
        return $this->hasOne(Menu::className(), ['id' => 'parent']);
    }

    public function getChildMenus()
    {
        return $this->hasMany(Menu::className(), ['parent' => 'id']);
    }

}
