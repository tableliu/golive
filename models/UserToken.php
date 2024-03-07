<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2017/5/8
 * Time: 15:52
 */

namespace app\models;

use app\components\Settings;
use Yii;

/**
 * This is the model class for table "{{%user_token}}".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $type
 * @property string $token
 * @property string $data
 * @property string $created_at
 * @property string $expired_at
 * @property string $wx_session_key
 * @property User $user
 */
class UserToken extends \amnah\yii2\user\models\UserToken
{
    const TYPE_REST_LOGIN = 0;

    /**
     * Generate/reuse a userToken
     * @param int $userId
     * @param int $type
     * @param string $session_key
     * @param string $data
     * @param string $expireTime
     * @return static
     */

    public static function tableName()
    {
        return Settings::DsnName() . ".user_token";
    }

    public static function generate($userId, $type, $data = null, $expireTime = null, $session_key = null)
    {

        // attempt to find existing record
        // otherwise create new
        $checkExpiration = false;
        if ($userId) {
            $model = static::findByUser($userId, $type, $checkExpiration);
        } else {
            $model = static::findByData($data, $type, $checkExpiration);
        }
        if (!$model) {
            $model = new static();
        }

        // set/update data
        $model->user_id = $userId;
        $model->type = $type;
        $model->data = $data;
        $model->created_at = gmdate("Y-m-d H:i:s");
        $model->expired_at = $expireTime;
        $model->token = Yii::$app->security->generateRandomString();
        $model->wx_session_key = $session_key;
        $model->save();
        $model->getErrors();
        return $model;
    }

    public static function getDb()
    {
        return Yii::$app->settings->getUserDB();

    }
}