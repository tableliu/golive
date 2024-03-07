<?php

namespace app\modules\easyforms\models;

use Yii;

/**
 * This is the model class for table "dictionary".
 *
 * @property int $id
 * @property int $type
 * @property string $name
 * @property int $updated_by
 * @property int $created_by
 * @property string|null $updated_at
 * @property string|null $created_at
 * @property int $pid
 */
class Dictionary extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dictionary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'name', 'updated_by', 'created_by', 'pid'], 'required'],
            [['type', 'updated_by', 'created_by', 'pid'], 'integer'],
            [['name'], 'string'],
            [['updated_at', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'name' => 'Name',
            'updated_by' => 'Updated By',
            'created_by' => 'Created By',
            'updated_at' => 'Updated At',
            'created_at' => 'Created At',
            'pid' => 'Pid',
        ];
    }
}
