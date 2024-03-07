<?php

namespace app\controllers;

use app\helpers\wxHelper;
use app\models\Equipment;
use app\models\EquipmentRecord;
use app\models\LiveRoom;
use app\models\NodeSendTime;
use app\models\Profile;
use app\models\User;
use app\models\WxAccessToken;
use app\queues\LiveRoomCloseAfterStreamerLeave;
use Yii;
use yii\console\ExitCode;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{

    /**
     * {@inheritdoc}
     */

    public function behaviors()
    {
        return [

            'verbs' => ['class' => VerbFilter::className(), 'actions' => [//                    'logout' => ['post'],
            ],],];
    }

    /**
     * {@inheritdoc}
     */
    //    public function actions()
    //    {
    //        return [
    //            'error' => [
    //                'class' => 'yii\web\ErrorAction',
    //            ],
    //            'captcha' => [
    //                'class' => 'yii\captcha\CaptchaAction',
    //                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
    //            ],
    //        ];
    //    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        // LiveRoom::closeRoomDelay(888, 'ROOM_DESTROY_REASON_ROOM_STREAMER_LEAVE', Yii::$app->params['LiveRoom.StreamerLeaveSecs']);
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect('/site/contact');
        }

        $model->password = '';

        return $this->render('login', ['model' => $model,]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        $session = Yii::$app->getSession();
        if (Yii::$app->user->enableSession && isset($session)) {
            $session->destroy();
        }
        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');
            return $this->refresh();
        }
        return $this->render('contact', ['model' => $model,]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionSendMsg()
    {
        $time = date('Y-m-d H:i:s', time());
        $sendNodeModel = $this->getSendData();
        if (empty($sendNodeModel))
            return ExitCode::OK;
        $name = "";
        $node_title = "";
        $wx_openid = "";
        $succeed_user = "";
        $failed_user = "";

        foreach (ArrayHelper::index($sendNodeModel, null, 'user_id') as $key => $nodes) {
            foreach ($nodes as $node) {
                $node['wx_g_openid'] ?
                    $wx_openid = $node['wx_g_openid'] : $node['wx_g_openid'] = wxHelper::getServiceAccountUserOpenid($node['wx_unionid']);
                $wx_openid = $node['wx_g_openid'];
                $full_name = $node['full_name'];
                $name .= $node['name'] . ",";
                $node_title .= $node['title'] . ",";
            }
            $data = $this->templateMsg($name, $node_title, $wx_openid, $full_name);
            if (!isset($data['errcode']) || $data['errcode'] == 0) {
                NodeSendTime::deleteAll(['and', 'user_id = :userId', ['<=', 'send_time', $time]], [':userId' => $key]);
                $succeed_user .= $key . ",";
            } else {
                $failed_user .= $key . ",";
            }


        }
        echo "成功" . $succeed_user . "\n";
        echo "失败" . $failed_user . "\n";
        return ExitCode::OK;

    }

    public function templateMsg($name, $node_title, $wx_openid, $full_name)
    {
        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);
        $token = wxHelper::getServiceAccountToken();
        $url = Yii::$app->params['Wx.MessageTemplateSend.Url'] . '?access_token=' . $token;
        $curl = $client->createRequest()
            ->setMethod('POST')
            ->setUrl($url)
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setContent(Json::encode(
                [
                    'touser' => $wx_openid,
                    'template_id' => Yii::$app->params['Wx.Self.Fw.Template_Id'],
                    'url' => "",
                    'miniprogram' => [
                        'appid' => Yii::$app->params['Wx.Self.Appid'],
                        'page' => 'pages/staff/NodeCheck/index?joinby="gzh"',
                    ],
                    'data' => [
                        "first" => [
                            "value" => $full_name . "：您有未完成的任务"
                        ],
                        "keyword1" => [
                            "value" => rtrim($name, ",")
                        ],
                        "keyword4" => [
                            "value" => rtrim($node_title, ",")
                        ],
                        "remark" => [
                            "value" => "暂无"
                        ],

                    ]

                ]));
        return $curl->send()->getData();
    }

    public function getSendData()
    {
        $time = date('Y-m-d H:i:s', time());
        $EquipmentRecordModel = $nodes = EquipmentRecord::find()
            ->select(['id'])
            ->andWhere(['finish_time' => null])
            ->asArray()
            ->groupBy('equipment_id')
            ->orderby('step asc')
            ->all();
        $ids = ArrayHelper::getColumn($EquipmentRecordModel, 'id');
        return NodeSendTime::find()
            ->select(['max(N.id) AS id', 'full_name', 'N.user_id', 'N.equipment_id', 'title', 'E.name', 'wx_openid', 'wx_g_openid', 'wx_unionid'])
            ->from(NodeSendTime::tableName() . ' N')
            ->leftJoin(EquipmentRecord::tableName() . ' R', 'R.equipment_id =  N.equipment_id')
            ->leftJoin(Equipment::tableName() . ' E', 'E.id = N.equipment_id')
            ->leftJoin(User::tableName() . ' U', 'U.id = N.user_id')
            ->leftJoin(Profile::tableName() . ' P', 'P.user_id = U.id')
            ->where(['in', 'R.id', $ids])
            ->andwhere(['<=', 'send_time', $time])
            ->andwhere(['!=', 'wx_openid', ''])
            ->andwhere(['!=', 'wx_unionid', ''])
            ->orderby('N.id desc')
            ->groupBy('N.equipment_id')
            ->asArray()
            ->all();

    }


}
