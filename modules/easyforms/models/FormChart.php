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
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "form_chart".
 *
 * @property integer $form_id
 * @property string $name
 * @property string $label
 * @property string $title
 * @property string $type
 * @property integer $width
 * @property integer $height
 * @property integer $gsX
 * @property integer $gsY
 * @property integer $gsW
 * @property integer $gsH
 * @property integer $created_at
 * @property integer $updated_at
 */
class FormChart extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%form_chart}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['form_id', 'name', 'label', 'title', 'type', 'width', 'height', 'gsX', 'gsY', 'gsW', 'gsH'], 'required'],
            [['form_id', 'width', 'height', 'gsX', 'gsY', 'gsW', 'gsH', 'created_at', 'updated_at'], 'integer'],
            [['name', 'label', 'title', 'type'], 'string', 'max' => 255],
            [['form_id', 'name'], 'unique', 'targetAttribute' => ['form_id', 'name'],
                'message' => 'The combination of Form ID and Name has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'form_id' => Yii::t('form', 'Form ID'),
            'name' => Yii::t('form', 'Name'),
            'label' => Yii::t('form', 'Label'),
            'title' => Yii::t('form', 'Title'),
            'type' => Yii::t('form', 'Type'),
            'width' => Yii::t('form', 'Width'),
            'height' => Yii::t('form', 'Height'),
            'gsX' => Yii::t('form', 'Gs X'),
            'gsY' => Yii::t('form', 'Gs Y'),
            'gsW' => Yii::t('form', 'Gs W'),
            'gsH' => Yii::t('form', 'Gs H'),
            'created_at' => Yii::t('form', 'Created At'),
            'updated_at' => Yii::t('form', 'Updated At'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getForm()
    {
        return $this->hasOne(Form::className(), ['id' => 'form_id']);
    }
}
