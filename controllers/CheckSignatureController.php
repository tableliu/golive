<?php

namespace app\controllers;

use yii\web\Controller;
class CheckSignatureController extends Controller
{
    // WeChat template message validation
    public function actionCheckSignature ()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "Ve5oeDQAutW8QJ5NXJ";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            echo $_GET['echostr'];
            exit();
        } else {
            return false;
        }

    }
}