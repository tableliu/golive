<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/8/2
 * Time: 17:48
 */

namespace app\helpers;


use app\models\EasemobAdmin;
use Yii;
use yii\httpclient\Client;

class EasemobHelper
{
    const PROXY = false;

    private static function buildGetAdminTokenRequest()
    {
        $request = [
            'method' => 'POST',
            'url' => Yii::$app->params['LiveStreaming.Domain'] . '/' . Yii::$app->params['LiveStreaming.OrgName'] . '/' . Yii::$app->params['LiveStreaming.AppName'] . '/token',
            'format' => Client::FORMAT_JSON,
            'proxy' => self::PROXY,
            'data' =>
                [
                    'grant_type' => "client_credentials",
                    'client_id' => Yii::$app->params['LiveStreaming.ClientId'],
                    'client_secret' => Yii::$app->params['LiveStreaming.ClientSecret'],
                ]
        ];
        return $request;
    }

    public static function getAdminToken()
    {
        $admin = EasemobAdmin::find()
            ->where(['client_id' => Yii::$app->params['LiveStreaming.ClientId']])
            ->one();
        if (!empty($admin)) {
            if ((strtotime($admin->created_at) + $admin->expires_in + 60) > time())
                return $admin->access_token;
            EasemobAdmin::deleteAll(['client_id' => Yii::$app->params['LiveStreaming.ClientId']]);
        }

        $request = self::buildGetAdminTokenRequest();
        if (empty($request)) {
            return;
        }
        $data = CurlHelper::send_curl_get_json_data($request);
        if (empty($data)) {
            return;
        }
        $admin = new EasemobAdmin([
            'client_id' => Yii::$app->params['LiveStreaming.ClientId'],
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in']
        ]);
        if ($admin->save())
            return $admin->access_token;
        return null;
    }

    private static function buildGetUserTokenRequest($username, $password)
    {
        $request = [
            'method' => 'POST',
            'url' => Yii::$app->params['LiveStreaming.Domain'] . '/' . Yii::$app->params['LiveStreaming.OrgName'] . '/' . Yii::$app->params['LiveStreaming.AppName'] . '/token',
            'referer' => 'https://a1.easemob.com',
            'proxy' => self::PROXY,
            'format' => Client::FORMAT_JSON,
            'data' =>
                [
                    'grant_type' => "password",
                    'username' => $username,
                    'password' => $password,
                    'timestamp' => time()
                ]
        ];
        return $request;
    }

    public static function fetchUserToken($username, $password)
    {
        $request = self::buildGetUserTokenRequest($username, $password);
        if (empty($request)) {
            return;
        }
        $data = CurlHelper::send_curl_get_json_data($request);
        if (empty($data)) {
            return;
        }
        return [
            'access_token' => $data['access_token'],
            'expires_in' => $data['expires_in'],
        ];

    }

    private static function genRandKey($count)
    {
        $res = '';
        for ($i = 0; $i < $count; $i++) {
            $var = rand(1, 9);
            $res = $res . strval($var);
        }
        return $res;
    }

    private static function buildRegisterUserRequest()
    {
        $username = self::genRandKey(16);
        $password = self::genRandKey(6);

        $request = [
            'method' => 'POST',
            'url' => Yii::$app->params['LiveStreaming.Domain'] . '/' . Yii::$app->params['LiveStreaming.OrgName'] . '/' . Yii::$app->params['LiveStreaming.AppName'] . '/users',
            'headers' => [
                'Authorization' => 'Bearer ' . self::getAdminToken()
            ],
            'proxy' => self::PROXY,
            'format' => Client::FORMAT_JSON,
            'data' =>
                [
                    'username' => $username,
                    'password' => $password,
                    'nickname' => ''
                ]
        ];
        return $request;
    }

    public static function registerUser()
    {
        $request = self::buildRegisterUserRequest();
        if (empty($request)) {
            return null;
        }
        $data = CurlHelper::send_curl_get_json_data($request);
        if (empty($data) || empty($data['entities'])) {
            return null;
        }
        return [
            'username' => $data['entities'][0]['username'],
            'password' => $request['data']['password'],
            'uuid' => $data['entities'][0]['uuid'],
        ];
    }

    private static function buildCreateConfrRequest($username, $token, $roomName, $password, $nickname = "", $ext = [])
    {
        $request = [
            'method' => 'POST',
            'url' => Yii::$app->params['LiveStreaming.Domain'] . '/' . Yii::$app->params['LiveStreaming.OrgName'] . '/' . Yii::$app->params['LiveStreaming.AppName'] . '/conferences/room',
            'format' => Client::FORMAT_JSON,
            'proxy' => self::PROXY,
            'data' =>
                [
                    'roomName' => $roomName,
                    'password' => $password,
                    'memName' => Yii::$app->params['LiveStreaming.OrgName'] . '#' . Yii::$app->params['LiveStreaming.AppName'] . '_' . $username,
                    'token' => $token,
                    'nickName' => $nickname,
                    'ext' => $ext
                ]
        ];
        return $request;
    }

    public static function createConference($username, $token, $nickname = "", $ext = [])
    {
        $roomName = self::genRandKey(16);
        $password = self::genRandKey(6);
        $request = self::buildCreateConfrRequest($username, $token, $roomName, $password, $nickname, $ext);
        if (empty($request)) {
            return null;
        }
        $data = CurlHelper::send_curl_get_json_data($request);
        if (empty($data) || empty($data['entities'])) {
            return null;
        }
        return [
            'confrName' => $roomName,
            'confrId' => $data['confrId'],
            'password' => $data['password'],
            'type' => $data['type'],
            'roleToken' => $data['roleToken']
        ];
    }


    private static function buildCreateConfrAndJoinRequest($user_name, $user_token, $confr_password, $confrType)
    {
        $browser = 'chrome';
        $browserVersion = 84;
        $version = "2.1.3";
        $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36";

        $request = [
            'method' => 'POST',
            'url' => Yii::$app->params['LiveStreaming.Domain'] . '/easemob/rtc/req/ticket',
            'format' => Client::FORMAT_JSON,
            'proxy' => self::PROXY,
            'data' =>
                [
                    'uid' => Yii::$app->params['LiveStreaming.OrgName'] . '#' . Yii::$app->params['LiveStreaming.AppName'] . '_' . $user_name,
                    'token' => $user_token,
                    'terminal' => [
                        'browser' => $browser,
                        'browserVersion' => $browserVersion,
                        'version' => $version,
                        'userAgent' => $ua
                    ],
                    'confrType' => $confrType,
                    'password' => $confr_password,
                    'rec' => false,
                    'recMerge' => false,
                    'supportWechatMiniProgram' => false
                ]
        ];
        return $request;
    }

    public static function createConferenceAndJoin($username, $token, $confrType = 10)
    {
        $roomName = self::genRandKey(16);
        $password = self::genRandKey(6);
        $request = self::buildCreateConfrAndJoinRequest($username, $token, $password, $confrType);

        if (empty($request)) {
            return null;
        }
        $data = CurlHelper::send_curl_get_json_data($request);
        if (empty($data)) {
            return null;
        }
        return [
            'confrName' => $roomName,
            'confrId' => $data['confrId'],
            'password' => $data['password'],
            'type' => $data['type'],
            'roleToken' => $data['roleToken']
        ];
    }

    public static function buildDestroyConfrRequest($confr_id, $role_token)
    {
        $request = [
            'method' => 'POST',
            'url' => Yii::$app->params['LiveStreaming.Domain'] . '/easemob/rtc/disband/conference',
            'format' => Client::FORMAT_JSON,
            'proxy' => self::PROXY,
            'data' =>
                [
                    'confrId' => $confr_id,
                    'roleToken' => $role_token
                ]
        ];
        return $request;
    }

    public static function destroyConference($confr_id, $role_token)
    {
        $request = self::buildDestroyConfrRequest($confr_id, $role_token);
        if (empty($request)) {
            return null;
        }
        $data = CurlHelper::send_curl_get_json_data($request);
        if (empty($data) || $data['error'] != 0) {
            return null;
        }
        return true;
    }
}