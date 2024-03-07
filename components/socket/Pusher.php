<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2019/12/25
 * Time: 14:36
 */

namespace app\components\socket;


use app\common\Consts;
use app\components\socket\rpc\SocketRpcApplication;
use app\components\socket\Session\SessionProvider;
use app\components\socket\Wamp\WampCloseApplication;
use app\components\socket\Wamp\WampOpenApplication;
use app\helpers\DebugHelper;
use app\models\Online;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use Yii;
use yii\helpers\Json;


class Pusher implements WampServerInterface
{
    const TOPIC_IIP_BASE = 'iip_base';

    /**
     * A lookup of all the topics clients have subscribed to
     */
    protected $subscribedTopics = array();

    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        echo '---before open memory' . DebugHelper::memory_usage() . "\r\n";
        $config = require __DIR__ . '/../../config/socket_wamp_open.php';
        $config['conn'] = $conn;
        $app = new WampOpenApplication($config);
        $app->run();

        Yii::$app = null;
        unset($app);
//        gc_collect_cycles();

        echo '---after open memory ' . DebugHelper::memory_usage() . "\r\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        echo '---before close memory' . DebugHelper::memory_usage() . "\r\n";
        $config = require __DIR__ . '/../../config/socket_wamp_close.php';
        $config['conn'] = $conn;
        $app = new WampCloseApplication($config);
        $app->run();

        Yii::$app = null;
        unset($app);
        echo '---after close memory' . DebugHelper::memory_usage() . "\r\n";
    }

    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        printf("Subscribe " . $topic->getId() . " \r\n");
        $this->subscribedTopics[$topic->getId()] = $topic;
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        printf("UnSubscribe \r\n");
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        echo '---before call memory' . DebugHelper::memory_usage() . "\r\n";
        $config = require __DIR__ . '/../../config/socket_rpc.php';
        $config['conn'] = $conn;
        $config['call_id'] = $id;
        $config['pusher'] = $this;

        $config['components']['request']['_data'] = $params;
        $config['components']['request']['_uri'] = $topic->getId();
        $config['components']['request']['_cid'] = $id;
        $app = new SocketRpcApplication($config);
        $app->run();
        Yii::info("onCall \r\n");
        echo '---after call memory' . DebugHelper::memory_usage() . "\r\n";
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        printf("Publish \r\n");
        $conn->close();
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
        printf("Error: " . $e->getMessage() . "\r\n");
    }

    /**
     * @param string ZmqEntryData Obj, JSON'ified string we'll receive from ZeroMQ
     * @return bool|void
     */
    public function onPushMsgEntry($entry)
    {
        try {
            printf("onPushMsgEntry \r\n");
            $entryData = Json::decode($entry);
            $topic_key = isset($entryData['topic']) ? $entryData['topic'] : Pusher::TOPIC_IIP_BASE;
            // If the lookup topic object isn't set there is no one to publish to
            if (!array_key_exists($topic_key, $this->subscribedTopics)) {
                return;
            }

            $topic = $this->subscribedTopics[$topic_key];
            $exclude = isset($entryData['blacklist']) ? $entryData['blacklist'] : [];
            $eligible = isset($entryData['whitelist']) ? $entryData['whitelist'] : [];
            $msg = isset($entryData['msg']) ? $entryData['msg'] : "";
            if (empty($eligible))
                return $topic->broadcast($msg, $exclude, $eligible);
            else {
                return $this->sendMsgTo($msg, $eligible, $topic_key); // 优化了broadcast在指定接受者push的效率
            }
        } catch (\Exception $e) {
            printf("error: " . $e->getMessage());
        }
    }

    /**
     * @param $msg
     * @param string|array $wamp_session_id 尽量不要出现socket不在线的用户，否则会大大降低效率
     * @param string $topic_key
     * @return bool
     */
    public function sendMsgTo($msg, $wamp_session_id, $topic_key = Pusher::TOPIC_IIP_BASE)
    {
        $success = false;
        if (!array_key_exists($topic_key, $this->subscribedTopics)) {
            return $success;
        }
        $topic = $this->subscribedTopics[$topic_key];
        if (is_array($wamp_session_id)) {
            $count = count($wamp_session_id);
            $sent_count = 0;
            foreach ($topic->getIterator() as $client) {
                if (in_array($client->WAMP->sessionId, $wamp_session_id, false)) {
                    $client->event($topic->getId(), $msg);
                    $success = true; //发成功一个就算成功
                    $sent_count += 1;
                    if ($sent_count == $count)
                        break;
                }
            }
        } else {
            foreach ($topic->getIterator() as $client) {
                if ($wamp_session_id == $client->WAMP->sessionId) {
                    $client->event($topic->getId(), $msg);
                    $success = true;
                    break;
                }
            }
        }

        return $success;
    }

    public function sendBaseMsgToUser($msg, $user_id, $topic_key = Pusher::TOPIC_IIP_BASE)
    {
        $success = false;
        if (!array_key_exists($topic_key, $this->subscribedTopics)) {
            return $success;
        }
        $topic = $this->subscribedTopics[$topic_key];

        if (is_array($user_id)) {
            $wamp_session_id = Online::find()->where(['in', 'user_id', $user_id])->all();
            $count = count($wamp_session_id);
            if ($count == 0)
                return $success;
            $sent_count = 0;
            foreach ($topic->getIterator() as $client) {
                if (in_array($client->WAMP->sessionId, $wamp_session_id, false)) {
                    $client->event($topic->getId(), $msg);
                    $success = true; //发成功一个就算成功
                    $sent_count += 1;
                    if ($sent_count == $count)
                        break;
                }
            }
        } else {
            $wamp_session_id = Online::find()->where(['in', 'user_id', $user_id])->one();
            if (!isset($wamp_session_id) || empty($wamp_session_id))
                return $success;

            foreach ($topic->getIterator() as $client) {
                if ($wamp_session_id == $client->WAMP->sessionId) {
                    $client->event($topic->getId(), $msg);
                    $success = true;
                    break;
                }
            }
        }

        return $success;
    }

}