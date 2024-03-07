<?php

namespace app\models;

use app\modules\easyforms\models\Form;
use app\modules\onsite\models\MobileJobInfo;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "video_job".
 *
 * @property int $id
 * @property int $job_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $name
 * @property string $extension
 * @property int $size
 * @property string $submission_id;
 * @property int $updated_by
 * @property int $created_by
 * @property string $longitude
 * @property string $latitude
 * @property string $formatted_address
 * @property string $start_time
 * @property string $end_time
 */
class VideoRecorder extends \app\common\IIPActiveRecord
{
    /**
     * @inheritdoc
     */
    const FILES_DIRECTORY = "upload/mobile_files";

    public static function tableName()
    {
        return 'video_record';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'safe'],
            [['updated_by', 'created_by', 'job_id', 'size'], 'integer'],
            [['extension', 'name', 'longitude', 'latitude'], 'string', 'max' => 255],
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

    public function getFilePath($v_id)
    {
        return $this->getFileDirectory($v_id) . '/' . $this->getFileName();
    }

    public function getFileName()
    {
        return $this->name . "." . $this->extension;
    }

    public function getFileDirectory($v_id)
    {
        return self::FILES_DIRECTORY . '/' . $v_id;
    }

    public function getFileImageDir($created_at)
    {
        $time = date('Y-m-d', strtotime($created_at));
        return self::FILES_DIRECTORY . '/images/' . $time;
    }


    public function getVideo()
    {
        return $this->hasOne(VideoJob::className(), ['id' => 'job_id']);
    }

    public function getData($submissions_id, $created_at)
    {
        $array_id = explode(",", $submissions_id);
        $image_data = (new Query())
            ->select(['id', 'm_v_id', 'content', 'image_timeline', 'image'])
            ->from(VideoJobTimeline::tableName())
            ->where(['in', 'm_v_id', $array_id])
            ->all();
        $data = [];
        foreach ($image_data as $key => $val) {
            $data[$key]['image'] = $val['image'];
            $data[$key]['m_v_id'] = $val['m_v_id'];
            $data[$key]['image_timeline'] = $val['image_timeline'];
            $data[$key]['content'] = $val['content'];
        }
        $result = ArrayHelper::index($data, null, 'm_v_id');
        $step_data = [];
        $num = 1;
        foreach ($result as $key => $v) {
            $new_data['image_data'] = $v;
            unset($v['m_v_id']);
            $data = (new Query())
                ->select(['A.title', 'A.id', 'B.id as m_id', 'B.timeline'])
                ->from(VideoStepEdit::tableName() . ' A')
                ->leftJoin(MobileJobInfo::tableName() . ' B', 'A.id = B.s_id')
                ->where(['B.id' => $key])
                ->one();

            $new_data['step_id'] = $data['id'];
            $new_data['timeline'] = $data['timeline'];
            $new_data['title'] = $data['title'];
            if ($num == 1) {
                $new_data['time_length'] = $data['timeline'];
            } else {
                $up_timeline = MobileJobInfo::find()->where(['<', 'id', $data['m_id']])->orderBy("id desc")->one()['timeline'];
                $new_data['time_length'] = $data['timeline'] - $up_timeline;
            }
            $step_data[] = $new_data;
            $num++;

        }


        return $step_data;

    }


}
