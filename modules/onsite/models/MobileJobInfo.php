<?php

namespace app\modules\onsite\models;

use app\components\JobBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;

/**
 * This is the model class for table "mobile_job_info".
 *
 * @property int $id
 * @property int $s_id
 * @property int $v_id
 * @property int $timeline
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 */
class MobileJobInfo extends \app\common\IIPActiveRecord
{
    /**
     * @inheritdoc
     */

    public static function tableName()
    {
        return 'video_record_step';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_by', 'updated_by','s_id','v_id'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */

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
