<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2019/12/12
 * Time: 10:07
 */

namespace app\commands;


use app\components\socket\Pusher;
use app\components\socket\Session\MongoDbSessionHandler;
use app\components\socket\Session\SessionProvider;
use app\models\Online;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\Server;
use React\ZMQ\Context;
use Yii;
use ZMQ;

class ServerController extends \yii\console\Controller
{
    public function actionRun($port)
    {
        // 清空在线用户
        Online::deleteAll();

        $loop = Factory::create();
        $pusher = new Pusher;
        // 使用ZMQ监听服务端
        $context = new Context($loop);
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);
        $pull->bind(Yii::$app->params['ZMQ_SOCKET_DSN']); // Binding to 127.0.0.1 means the only client that can connect is itself
        $pull->on('message', array($pusher, 'onPushMsgEntry'));

        // 使用WebSocket服务监听前端
        $webSock = new Server('tcp://127.0.0.1:' . $port, $loop);
        $wsServer = new WsServer(
            new \app\components\socket\Wamp\WampServer(
                $pusher
            )
        );
        $wsServer->enableKeepAlive($loop);

        $sp = new SessionProvider($wsServer, new MongoDbSessionHandler());
        $webServer = new IoServer(
            new HttpServer(
                $sp
            // new \app\components\socket\SessionProvider($wsServer)
            ),
            $webSock,
            $loop
        );
        $webServer->run();
    }
}