<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/4/14
 * Time: 22:23
 */

namespace app\components\socket\rpc\formatter;


class JsonResponseFormatter extends \yii\web\JsonResponseFormatter
{
    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response)
    {
        if ($this->useJsonp) {
            $this->formatJsonp($response);
        } else {
            $this->formatJson($response);
        }
    }
}