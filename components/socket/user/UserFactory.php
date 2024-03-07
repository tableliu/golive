<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/28
 * Time: 23:02
 */

namespace app\components\socket\user;


use app\common\Consts;
use app\components\socket\Session\SessionProvider;
use Ratchet\ConnectionInterface;
use Yii;

class UserFactory
{
    /**
     * @param $conn ConnectionInterface
     * @return \amnah\yii2\user\components\User | OnlineInterface | object $user
     * @throws \yii\base\InvalidConfigException
     */
    public static function create($conn)
    {
        if (Consts::LIVE_USER_TYPE_MOBILE == $conn->Session->get(SessionProvider::ClientTypeSessionKey))
            return Yii::createObject([
                'class' => 'app\components\socket\user\MobileWebUser',
            ]);
        else
            return Yii::createObject([
                'class' => 'app\components\socket\user\WebUser',
            ]);
    }
}