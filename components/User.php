<?php


namespace app\components;

use app\common\exception\UnauthorizedRestException;
use yii;
use app\common\RestResult;
use app\common\Consts;

/**
 * Class User
 * @package app\components
 *
 * User Component
 */
class User extends \yii\web\User
{
    /**
     * @inheritdoc
     */
    public $identityClass = 'app\models\User';


    public function loginRequired($checkAjax = true, $checkAcceptHeader = true)
    {
        $result = Yii::createObject(
            [
                'class' => RestResult::className(),
                'code' => Consts::REST_NO_LOGIN,
                'msg' => Yii::t('app', 'Sign in to start your session')
            ]);

        $respones = Yii::$app->getResponse();
        $respones->data = $result;
        return;
    }


    public static function findIdentity($id)
    {
        return static::findOne($id);
    }

    public function userSkip($action)
    {
        if ($this->getIsGuest()) {
            throw new UnauthorizedRestException();
        }
        return;
    }

    public function userDeny()
    {

        if ($this->getIsGuest()) {
            $this->loginRequired();
        } else {
            $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_FORBIDDEN,
                    'data' => "",
                    'msg' => Yii::t('yii', 'You are not allowed to perform this action.')
                ]);
            $response = Yii::$app->getResponse();
            $response->data = $result;
            return;
        }
    }


}