<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.0
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

namespace app\modules\easyforms\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use app\components\behaviors\SluggableBehavior;
use app\components\JsonToArrayBehavior;

/**
 * This is the model class for table "template".
 *
 * @property integer $id
 * @property integer $category_id
 * @property string $name
 * @property string $description
 * @property string $builder
 * @property string $html
 * @property integer $promoted
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 *
 * @property TemplateCategory $category
 * @property User $author
 * @property User $lastEditor
 *
 */
class Template extends ActiveRecord
{

    const PROMOTED_OFF = 0;
    const PROMOTED_ON = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%template}}';
    }

    /**
     * @inheritdoc
     */
    public static function primaryKey()
    {
        return ['id'];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            BlameableBehavior::className(),
            TimestampBehavior::className(),
            [
                'class' => SluggableBehavior::className(),
                'attribute' => 'name',
                'ensureUnique' => true,
            ],
            'builderField' => [
                'class' => JsonToArrayBehavior::className(),
                'jsonFieldName' => 'builder',
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['category_id', 'promoted', 'created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'required'],
            [['builder', 'html'], 'string'],
            [['name', 'description'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('form', 'ID'),
            'category_id' => Yii::t('form', 'Category ID'),
            'name' => Yii::t('form', 'Name'),
            'description' => Yii::t('form', 'Description'),
            'builder' => Yii::t('form', 'Builder'),
            'html' => Yii::t('form', 'Html'),
            'promoted' => Yii::t('form', 'Promoted'),
            'created_by' => Yii::t('form', 'Created By'),
            'updated_by' => Yii::t('form', 'Updated By'),
            'created_at' => Yii::t('form', 'Created At'),
            'updated_at' => Yii::t('form', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategory()
    {
        return $this->hasOne(TemplateCategory::className(), ['id' => 'category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLastEditor()
    {
        return $this->hasOne(User::className(), ['id' => 'updated_by']);
    }
}
