<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/1/9
 * Time: 17:49
 */

namespace app\components\socket\user;


use app\common\Consts;
use app\common\SocketPushResult;
use app\components\socket\Session\SessionProvider;
use app\helpers\DebugHelper;
use app\helpers\SocketHelper;
use app\helpers\ZmqEntryData;
use app\models\Online;
use Yii;
use yii\base\InvalidValueException;
use yii\web\IdentityInterface;

class WebUser extends \amnah\yii2\user\components\User implements OnlineInterface
{
    public $identityClass = 'app\models\User';
    protected $_identity = false;

    public function getIdentity($autoRenew = true)
    {
        if ($this->_identity === false) {
            if ($autoRenew && isset(Yii::$app->session)) {
                try {
                    $this->_identity = null;
                    $this->renewAuthStatus();
                } catch (\Exception $e) {
                    $this->_identity = false;
                    throw $e;
                } catch (\Throwable $e) {
                    $this->_identity = false;
                    throw $e;
                }
            } else {
                return null;
            }
        }

        return $this->_identity;
    }

    protected function renewAuthStatus()
    {
        $session = Yii::$app->session;
        $id = $session->get($this->idParam);

        if ($id === null) {
            $identity = null;
        } else {
            /* @var $class IdentityInterface */
            $class = $this->identityClass;
            $identity = $class::findIdentity($id);
        }

        $this->setIdentity($identity);

        // 以下代码不可使用，否则会出现 http 与 ws分别生成sessionId，然后两个中的一个会使用这个sessionId无法获取到对应的identity。因此目前 ws必须在同域名的http之后发送，这样才可以使用合适的cookie获取identity

        //        if ($this->enableAutoLogin) {
        //            if ($this->getIsGuest()) {
        //                $this->loginByCookie();
        //            } elseif ($this->autoRenewCookie) {
        //                $this->renewIdentityCookie();
        //            }
        //        }

    }

    public function setIdentity($identity)
    {
        if ($identity instanceof IdentityInterface) {
            $this->_identity = $identity;
        } elseif ($identity === null) {
            $this->_identity = null;
        } elseif ($identity === false) {
            $this->_identity = false;
        } else {
            throw new InvalidValueException('The identity object must implement IdentityInterface.');
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

    /**
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function online()
    {
        if (!isset(Yii::$app->user) ||
            !isset(Yii::$app->user->identity))
            return false;
        $user_id = Yii::$app->user->getIdentity()->getId();
        $session_id = Yii::$app->session->getId();

        echo '--- memory ' . DebugHelper::memory_usage() . "\r\n";

        // kick others who login with this user_id, include diff tab
        $others = Online::find()
            ->andWhere(['user_id' => $user_id])
            ->andWhere(['not', ['session_id' => $session_id]])// this line allow diff tab with same user
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
                'session_id' => $session_id,
                'wamp_session_id' => $wamp_session_id,
                'client_type' => isset($client_type) ?
                    trim($client_type) :
                    null
            ]
        ))->save();
        if ($saved !== false)
            Online::pushUserOnlineOffline(1, $client_type);
        return $saved;
    }

    public function offline()
    {
        try {
            $wamp_session_id = Yii::$app->connection->WAMP->sessionId;
            $online = Online::findOne(['wamp_session_id' => $wamp_session_id]);
            $client_type = $online->client_type;
            if (isset($online)) {
                if ($online->delete())
                    Online::pushUserOnlineOffline(0, $client_type);
            }
            // these lines allow diff tab with same user
            $session_id = Yii::$app->session->getId();
            $others = Online::findOne(['session_id' => $session_id]);
            if ($others == null)
                Yii::$app->session->clear();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}