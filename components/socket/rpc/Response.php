<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/30
 * Time: 17:20
 */

namespace app\components\socket\rpc;

use app\models\Online;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\web\HeaderCollection;
use yii\web\ResponseFormatterInterface;

class Response extends \yii\base\Response
{
    /**
     * @event ResponseEvent an event that is triggered at the beginning of [[send()]].
     */
    const EVENT_BEFORE_SEND = 'beforeSend';
    /**
     * @event ResponseEvent an event that is triggered at the end of [[send()]].
     */
    const EVENT_AFTER_SEND = 'afterSend';
    /**
     * @event ResponseEvent an event that is triggered right after [[prepare()]] is called in [[send()]].
     * You may respond to this event to filter the response content before it is sent to the client.
     */
    const EVENT_AFTER_PREPARE = 'afterPrepare';

    const FORMAT_RAW = 'raw';
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML = 'xml';

    /**
     * @var array the formatters for converting data into the response content of the specified [[format]].
     * The array keys are the format names, and the array values are the corresponding configurations
     * for creating the formatter objects.
     * @see format
     * @see defaultFormatters
     */
    public $formatters = [];
    public $format = self::FORMAT_JSON;
    /**
     * @var mixed the original response data. When this is not null, it will be converted into [[content]]
     * according to [[format]] when the response is being sent out.
     * @see content
     */
    public $data;
    /**
     * @var string the response content. When [[data]] is not null, it will be converted into [[content]]
     * according to [[format]] when the response is being sent out.
     * @see data
     */
    public $content;
    public $isSent = false;
    public $cid;

    /**
     * Initializes this component.
     */
    public function init()
    {
        $this->formatters = array_merge($this->defaultFormatters(), $this->formatters);
    }

    /**
     * Clears the headers, cookies, content, status code of the response.
     */
    public function clear()
    {
        $this->data = null;
        $this->content = null;
        $this->isSent = false;
    }

    /**
     * @return array the formatters that are supported by default
     */
    protected function defaultFormatters()
    {
        return [
            self::FORMAT_HTML => [
                'class' => 'app\components\socket\rpc\formatter\HtmlResponseFormatter',
            ],
            self::FORMAT_XML => [
                'class' => 'app\components\socket\rpc\formatter\XmlResponseFormatter',
            ],
            self::FORMAT_JSON => [
                'class' => 'app\components\socket\rpc\formatter\JsonResponseFormatter',
            ],
            self::FORMAT_JSONP => [
                'class' => 'app\components\socket\rpc\formatter\JsonResponseFormatter',
                'useJsonp' => true,
            ],
        ];
    }

    protected function prepare()
    {
        $this->data = [
            'id' => $this->cid,
            'result' => $this->data
        ];
        if (isset($this->formatters[$this->format])) {
            $formatter = $this->formatters[$this->format];
            if (!is_object($formatter)) {
                $this->formatters[$this->format] = $formatter = Yii::createObject($formatter);
            }
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($this);
            } else {
                throw new InvalidConfigException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
        } elseif ($this->format === self::FORMAT_RAW) {
            if ($this->data !== null) {
                $this->content = $this->data;
            }
        } else {
            throw new InvalidConfigException("Unsupported response format: {$this->format}");
        }

        if (is_array($this->content)) {
            throw new InvalidArgumentException('Response content must not be an array.');
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidArgumentException('Response content must be a string or an object implementing __toString().');
            }
        }
    }

    /**
     * Sends the response content to the client.
     */
    protected function sendContent()
    {
        Yii::$app->getConnection()->callResult($this->cid, $this->content);
        return;
    }

    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }


}