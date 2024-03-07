<?php

namespace app\common;

use app\common\Consts;
use yii\web\HttpException;

class NotSelectProjectException extends HttpException
{

    public function __construct($message = null, $code = Consts::REST_NO_PID, \Exception $previous = null)
    {
        parent::__construct(200, $message, $code, $previous);
    }
}