<?php

namespace app\models;


use app\components\Settings;
use Yii;
use app\common\IIPActiveRecord;
use yii\web\UploadedFile;

/**
 * This is the model class for table "tbl_profile".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $full_name
 * @property string $avatar
 * @property string $avatar_url
 * @property string $remark
 * @property User $user
 * @property mixed $image
 * @property integer $gender
 */
class Profile extends IIPActiveRecord
{

    /**
     * @var string
     */
    public $avatarsDir = "upload/images/";
    public $itemTable = '{{%auth_assignment}}';

    /**
     * @var mixed image the attribute for rendering the file input
     * widget for upload on the form
     */
    public $image;

    /**
     * @inheritdoc
     */


    public static function tableName()
    {
        return Settings::DsnName() . ".profile";
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['full_name'], 'required'],
            [['full_name', 'avatar', 'telephone', 'remark'], 'string', 'max' => 255],
            [['gender'], 'integer'],
            ['telephone', 'match', 'pattern' => '/^1[0-9]{10}$/', 'message' => '输入正确的手机号码'],
            [['image'], 'image', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, gif, jpeg', 'maxSize' => 1024 * 1024],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'created_at' => Yii::t('app', 'Created At'),
            'updated_at' => Yii::t('app', 'Updated At'),
            'full_name' => Yii::t('app', 'Full Name'),
            'avatar' => Yii::t('app', 'Avatar'),
            'image' => Yii::t('app', 'Avatar'),
            'remark' => Yii::t('app', 'Remark'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'value' => function($event) {
                    return gmdate("Y-m-d H:i:s");
                },
            ],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Set user id
     *
     * @param int $userId
     * @return static
     */
    public function setUser($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Return the avatar full url
     *
     * @return string
     */
    public function getAvatarUrl()
    {
        if (!empty($this->avatar))
            return $this->avatar;
        if (!empty($this->avatar_url))
            return $this->avatar_url;
        return null;

    }

    public function getAvatar()
    {
        $avatar = isset($this->avatar) ? $this->avatar : null;
        return Yii::getAlias('@web') . $avatar;
    }


    /**
     * Return stored avatar path
     * @return string
     */
    public function getImageFile()
    {
        // Return a default image placeholder if the avatar is not found
        $avatar = isset($this->avatar) ? $this->avatarsDir . '/' . $this->avatar : null;
        return $avatar;
    }

    /**
     * Upload avatar image
     *
     * @return bool
     */
    public function uploadImage()
    {

        // Get the uploaded image instance.
        $image = UploadedFile::getInstance($this, 'image');

        // If no image was uploaded abort the upload
        if (empty($image)) {
            return false;
        }

        // Store a unique avatar name
        $this->avatar = Yii::$app->security->generateRandomString() . '.' . $image->extension;

        // The uploaded image instance
        return $image;

    }

    /**
     * Process deletion of avatar image
     *
     * @return boolean the status of deletion
     */
    public function deleteImage()
    {

        $file = $this->getImageFile();

        // check if file exists on server
        if (empty($file) || !file_exists($file)) {
            return false;
        }

        // check if uploaded file can be deleted on server
        if (!unlink($file)) {
            return false;
        }

        // if deletion successful, reset your file attributes
        $this->avatar = null;
        $this->image = null;

        return true;

    }

    public static function GetFullName($id)
    {
        $model = Profile::find()->where(['user_id' => $id])->one();
        return $model->full_name;
    }


    public static function getDb()
    {
        return Yii::$app->settings->getUserDB();
    }


}
