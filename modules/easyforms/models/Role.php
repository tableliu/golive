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

/**
 * This is the model class for table "tbl_role".
 *
 * @property integer $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property integer $can_admin
 * @property integer $can_edit_own_content
 *
 * @property User[] $users
 */
class Role extends \app\modules\user\models\Role
{
    /**
     * @var int Self-Editor user role
     */
    const ROLE_ADVANCED_USER = 3; // Can edit own content

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
    public function rules()
    {
        $rules = [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['can_admin', 'can_edit_own_content'], 'integer'],
        ];

        // add can_ rules
        foreach ($this->attributes() as $attribute) {
            if (strpos($attribute, 'can_') === 0) {
                $rules[] = [[$attribute], 'integer'];
            }
        }

        return $rules;

    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('form', 'ID'),
            'name' => Yii::t('form', 'Name'),
            'created_at' => Yii::t('form', 'Created At'),
            'updated_at' => Yii::t('form', 'Updated At'),
            'can_admin' => Yii::t('form', 'Can Admin'),
            'can_edit_own_content' => Yii::t('form', 'Can Edit Own Content'),
        ];
    }

}