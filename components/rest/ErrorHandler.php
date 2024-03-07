<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/27
 * Time: 23:17
 */

namespace app\components\rest;

use Yii;
use yii\base\UserException;
use yii\web\HttpException;

class ErrorHandler extends \yii\web\ErrorHandler
{
    protected function convertExceptionToArray($exception)
    {
        if (!YII_DEBUG && !$exception instanceof UserException && !$exception instanceof HttpException) {
            $exception = new HttpException(500, Yii::t('yii', 'An internal server error occurred.'));
        }

        $array = [
            'code' => $exception->getCode(),
            'msg' => $exception->getMessage(),
        ];

        if (YII_DEBUG) {
            if ($exception instanceof HttpException) {
                $array['status'] = $exception->statusCode;
            }
            $array['type'] = get_class($exception);
            if (!$exception instanceof UserException) {
                $array['file'] = $exception->getFile();
                $array['line'] = $exception->getLine();
                $array['stack-trace'] = explode("\n", $exception->getTraceAsString());
                if ($exception instanceof \yii\db\Exception) {
                    $array['error-info'] = $exception->errorInfo;
                }
            }

            if (($prev = $exception->getPrevious()) !== null) {
                $array['previous'] = $this->convertExceptionToArray($prev);
            }
        }

        return $array;
    }
}