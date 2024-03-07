<?php

namespace app\models;

use app\common\IIPActiveRecord;
use app\components\Settings;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "project_user".
 *
 * @property int $id
 * @property int $user_id
 * @property int $pid 项目id
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Project $p
 * @property User $user
 */
class ProjectUser extends IIPActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'project_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'pid'], 'required'],
            [['user_id', 'pid', 'created_by', 'updated_by'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['pid'], 'exist', 'skipOnError' => true, 'targetClass' => Project::className(), 'targetAttribute' => ['pid' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'pid' => Yii::t('app', 'Pid'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getP()
    {
        return $this->hasOne(Project::className(), ['id' => 'pid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'value' => new Expression('NOW()'),
            ],
            BlameableBehavior::className(),
        ];
    }

    public static function getDb()
    {
        return Yii::$app->settings->getUserDB();

    }

    public function create($user_id)
    {
        $model = new ProjectUser();
        $key = Settings::getCurrentPid();
        $model->pid = Project::getID($key);
        $model->user_id = $user_id;
        $model->save(false);
        return true;


    }

    public static function getProject($pid, $user_id)
    {
        $id = Project::getID($pid);
        return ProjectUser::find()->where(['pid' => $id, 'user_id' => $user_id])->one();

    }


}
