<?php

namespace app\models;

use app\commands\PublicModel;
use app\common\IIPActiveRecord;
use app\components\DbManager;
use app\components\Settings;
use Yii;

/**
 * This is the model class for table "project".
 *
 * @property int $id
 * @property string $name 项目名称
 * @property string $pid
 * @property string $created_at
 * @property string $updated_at
 * @property string $db_config
 *
 * @property ProjectUser[] $projectUsers
 */
class Project extends IIPActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'project';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'pid'], 'required'],
            [['name', 'pid', 'db_config'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'pid' => Yii::t('app', 'Pid'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProjectUsers()
    {
        return $this->hasMany(ProjectUser::className(), ['pid' => 'id']);
    }

    public static function getDb()
    {
        return Yii::$app->settings->getUserDB();

    }

    public static function getID($pid)
    {
        return Project::findOne(['pid' => $pid])['id'];
    }

    //分配数据库
    public static function setUserProject($array_id, $user_id)
    {
        foreach ($array_id as $v) {
            $pid = Project::findOne(['pid' => $v])['id'];
            $model = new ProjectUser();
            $model->user_id = $user_id;
            $model->pid = $pid;
            $model->save(false);
        }
        return true;
    }

    public static function getProjectRole($user_id, $pid)
    {
        $role = Yii::$app->authManager->getAssignRoles($pid, $user_id);
        return $role;

    }

    public function search($user_id)
    {
        $array = Project::find()
            ->from(Project::tableName() . ' A')
            ->select(['A.pid', 'name'])
            ->leftJoin(ProjectUser::tableName() . ' B', 'B.pid = A.id')
            ->where(["user_id" => $user_id])
            ->asArray()
            ->all();

        return $array;
    }


    public function ProjectIndex()
    {
        $array = Project::find()
            ->from(Project::tableName() . ' A')
            ->select(['A.pid', 'name', 'B.pid as change'])
            ->distinct()
            ->leftJoin(ProjectUser::tableName() . ' B', 'B.pid = A.id')
            ->asArray()
            ->all();
        return $array;
    }

    /**
     * @return \yii\db\ActiveQuery
     */


    public function getProjectName()
    {
        return $this->name;
    }


}
