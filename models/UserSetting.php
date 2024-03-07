<?php

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "user_setting".
 *
 * @property int $id
 * @property string $updated_at
 * @property string $created_at
 * @property int $updated_by
 * @property int $created_by
 * @property string $setting
 * @property string $type
 */
class UserSetting extends \yii\db\ActiveRecord
{
    const USER_MOBILE_TYPE_SETTING = 'mobile';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_setting';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('user_db');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at', 'created_at', 'type'], 'safe'],
            [['updated_by', 'created_by'], 'integer'],
            [['setting'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
            'updated_by' => 'Updated By',
            'created_by' => 'Created By',
            'setting' => 'Setting',
        ];
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
}
