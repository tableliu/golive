<?php

namespace app\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "video_step_edit".
 *
 * @property int $id
 * @property int $step
 * @property string $title
 * @property string $content
 * @property string $created_at
 * @property string $updated_at
 * @property int $created_by
 * @property int $updated_by
 * @property int $v_id
 */
class VideoStepEdit extends \app\common\IIPActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'video_job_step';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['step', 'title', 'content'], 'required'],
            [['step', 'created_by', 'updated_by', 'v_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['title', 'content'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'step' => Yii::t('app', 'Step'),
            'title' => Yii::t('app', 'Title'),
            'content' => Yii::t('app', 'Content'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'created_by' => Yii::t('app', 'Created By'),
            'updated_by' => Yii::t('app', 'Updated By'),
            'v_id' => Yii::t('app', 'V ID'),
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
