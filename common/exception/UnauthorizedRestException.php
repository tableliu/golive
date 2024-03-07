<?php

namespace app\common\exception;

use app\common\Consts;
use Yii;
use yii\web\HttpException;

class UnauthorizedRestException extends HttpException
{
    public function __construct($message = null, $code = Consts::REST_NO_LOGIN, \Exception $previous = null)
    {
        $message = $message == null ? Yii::t("app", "Sign-in status is empty or expired.") : $message;
        parent::__construct(200, $message, $code, $previous);
    }
}