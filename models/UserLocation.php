<?php

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "user_location".
 *
 * @property int $id
 * @property int $user_id
 * @property string $longitude
 * @property string $latitude
 * @property string $updated_at
 * @property string $created_at
 * @property string $wamp_session_id
 * @property string $user_type
 * @property User $user
 */
class UserLocation extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_location';
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
            [['user_id'], 'integer'],
            [['longitude', 'latitude'], 'number'],
            [['updated_at', 'created_at', 'user_type'], 'safe'],
            [['wamp_session_id'], 'string'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
            'wamp_session_id' => 'Wamp Session ID',
        ];
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


    public static function getLastOnlineLocation($user_id)
    {
        return UserLocation::find()->where(['user_id' => $user_id])
            ->orderBy('id DESC')
            ->one();

    }
}
