<?php

namespace app\models;

use app\modules\easyforms\models\Form;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;

/**
 * This is the model class for table "video_job_timeline".
 *
 * @property int $id
 * @property int $m_v_id
 * @property int $image_timeline
 * @property string $image;
 * @property int $updated_by
 * @property int $created_by
 * @property string $created_at
 * @property string $updated_at
 * @property string $content
 */
class VideoJobTimeline extends \app\common\IIPActiveRecord
{
    /**
     * @inheritdoc
     */
    const FILES_DIRECTORY = "upload/mobile_files/images";
    public static function tableName()
    {
        return 'video_record_img';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'safe'],
            [['updated_by', 'created_by','image_timeline','m_v_id'], 'integer'],
            [['content'], 'string', 'max' => 255],
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


    public function getForm()
    {
        return $this->hasOne(Form::className(), ['id' => 'job_id']);
    }

    public function uploadFilmImages($name)
    {
        $filePath = self::FILES_DIRECTORY . '/' . date("Y-m-d") . '/';
        if (!is_dir($filePath)) {
            mkdir($filePath, 0777, true);
            chmod($filePath, 0777);
        }
        $images = UploadedFile::getInstances($this, $name);
        $imgArray = [];
        $oldImgs = [];
        //add
        foreach ($images as $image) {
            $fileName = Yii::$app->security->generateRandomString();
            $baseName = substr($image->name, 0, strlen($image->name) - strlen($image->extension) - 1);
            $imgArray[$fileName] = [
                'baseName' => $baseName,
                'ext' => $image->extension,
                'size' => $image->size
            ];
            $imgFilePath = $filePath . '/' . $fileName . '.' . $image->extension;
            $image->saveAs($imgFilePath);
        }

        $this->image = Json::encode(ArrayHelper::merge($oldImgs, $imgArray), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        // The uploaded image instances
        return $images;
    }




}
