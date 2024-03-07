<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/5/25
 * Time: 16:45
 */

namespace app\components\socket\user;

use app\common\SocketPushResult;
use app\components\socket\Session\SessionProvider;
use app\helpers\SocketHelper;
use app\helpers\ZmqEntryData;
use app\models\Online;
use Yii;
use yii\web\IdentityInterface;

class MobileWebUser extends \amnah\yii2\user\components\User implements OnlineInterface
{
    public $identityClass = 'app\models\User';
    protected $_identity = false;
    public $pattern = '/^Bearer\s+(.*?)$/';
    public $authHeader = 'Authorization';

    public function getIdentity($autoRenew = true)
    {
        if (!isset(Yii::$app->connection))
            return null;

        if ($this->_identity === false) {
            $this->_identity = $this->authenticate(Yii::$app->connection->httpRequest);
        }

        return $this->_identity;
    }

    public function authenticate($request)
    {
        $authHeader = isset($request->getHeaders()[$this->authHeader]) ? $request->getHeaders()[$this->authHeader][0] : null;
        printf($authHeader ? $authHeader : "");
        if ($authHeader !== null) {
            if ($this->pattern !== null) {
                if (preg_match($this->pattern, $authHeader, $matches)) {
                    $authHeader = $matches[1];
                } else {
                    return null;
                }
            }

            $identity = $this->loginByAccessToken($authHeader, get_class($this));
            if ($identity === null) {
                //                $this->handleFailure($response);
            }

            return $identity;
        }

        return null;
    }

    public function loginByAccessToken($token, $type = null)
    {
        /* @var $class IdentityInterface */
        $class = $this->identityClass;
        $this->_identity = $class::findIdentityByAccessToken($token, $type);
        if ($this->_identity) {
            return $this->_identity;
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIsGuest()
    {
        /** @var \amnah\yii2\user\models\User $user */
        $user = $this->getIdentity();
        if ($user && $user->banned_at) {
            $user = null;
        }

        return $user === null;
    }

    public function online()
    {
        if (!isset(Yii::$app->user) ||
            !isset(Yii::$app->user->identity))
            return false;
        $user_id = Yii::$app->user->getIdentity()->getId();

        // kick others who login with this user_id
        $others = Online::find()
            ->andWhere(['user_id' => $user_id])
            ->all();
        foreach ($others as $other) {
            // send msg
            $msg = Yii::createObject(
                [
                    'class' => SocketPushResult::class,
                    'uri' => 'base/push/kicked'
                ]);
            SocketHelper::pushMsg(new ZmqEntryData(
                [
                    'whitelist' => [$other['wamp_session_id']],
                    'msg' => $msg
                ]
            ));
            // remove online
            $other->delete();
            //remove session
            //            Yii::$app->session->destroy($other['session_id']);
            if (isset($other['session_id']) && !empty($other['session_id']))
                Yii::$app->session->remove($other['session_id']);
        }

        // save new online
        $wamp_session_id = Yii::$app->connection->WAMP->sessionId;
        $client_type = Yii::$app->session->get(SessionProvider::ClientTypeSessionKey);
        $saved = (new Online(
            [
                'user_id' => $user_id,
                'session_id' => "",
                'wamp_session_id' => $wamp_session_id,
                'client_type' => isset($client_type) ?
                    trim($client_type) :
                    null
            ]
        ))->save();
        if ($saved !== false) {
            Online::pushUserOnlineOffline(1, $client_type);
        }
        return $saved;
    }

    public function offline()
    {
        $wamp_session_id = Yii::$app->connection->WAMP->sessionId;
        $online = Online::findOne(['wamp_session_id' => $wamp_session_id]);
        $client_type = $online->client_type;
        if (isset($online)) {
            if ($online->delete())
                Online::pushUserOnlineOffline(0, $client_type);
        }


    }
}