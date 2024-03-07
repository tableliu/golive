<?php

namespace app\common\exception;

use app\common\Consts;
use Yii;
use yii\web\HttpException;

class ForbiddenRestException extends HttpException
{
    public function __construct($message = null, $code = Consts::REST_FORBIDDEN, \Exception $previous = null)
    {
        $message = $message == null ? Yii::t('yii', 'You are not allowed to perform this action.') : $message;
        parent::__construct($message, $code, $previous);
    }
}