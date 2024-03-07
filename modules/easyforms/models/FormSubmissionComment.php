<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.3.9
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

/**
 * This is the model class for table "{{%form_submission_comment}}".
 *
 * @property integer $id
 * @property integer $submission_id
 * @property integer $form_id
 * @property string $content
 * @property integer $status
 * @property integer $created_by
 * @property integer $updated_by
 * @property integer $created_at
 * @property integer $updated_at
 */
class FormSubmissionComment extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%form_submission_comment}}';
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
        ];
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        return ['id', 'content', 'authorName', 'submitted'];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['submission_id', 'form_id'], 'required'],
            [['submission_id', 'form_id', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'], 'integer'],
            [['content'], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('form', 'ID'),
            'submission_id' => Yii::t('form', 'Submission ID'),
            'form_id' => Yii::t('form', 'Form ID'),
            'content' => Yii::t('form', 'Content'),
            'status' => Yii::t('form', 'Status'),
            'created_by' => Yii::t('form', 'Created By'),
            'updated_by' => Yii::t('form', 'Updated By'),
            'created_at' => Yii::t('form', 'Created At'),
            'updated_at' => Yii::t('form', 'Updated At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->content = strip_tags(nl2br($this->content, false), '<br>');
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(User::className(), ['id' => 'created_by']);
    }

    /**
     * @return null|string Name of the author
     */
    public function getAuthorName()
    {
        if (isset($this->author) && isset($this->author->username)) {
            return $this->author->username;
        }

        return null;
    }

    /**
     * Created at with format
     *
     * @return string
     */
    public function getSubmitted()
    {
        return Yii::$app->formatter->asDatetime($this->created_at);
    }
}