<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/30
 * Time: 15:58
 */

namespace app\components\socket\rpc;


use Yii;
use yii\web\NotFoundHttpException;

class Request extends \yii\base\Request
{
    public $_data;
    public $_uri;
    public $_cid;

    public function resolve()
    {
        if (!isset($this->_uri) || empty($this->_uri))
            throw new NotFoundHttpException(Yii::t('yii', 'URI not found.'));

        return [$this->_uri, $this->_data, $this->_cid];
    }

    public function getHostInfo()
    {
        return '';
    }
}