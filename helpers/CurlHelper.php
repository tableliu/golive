<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2019/7/11
 * Time: 13:31
 */

namespace app\helpers;


use Yii;
use yii\helpers\Json;
use yii\httpclient\Client;

class CurlHelper
{
    const user_agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36';

    public static function send_curl_get_content($args = [])
    {
        $default = [
            'method' => 'GET',
            'user-agent' => self::user_agent,
            'url' => null,
            'referer' => '',
            'headers' => null,
            'data' => [],
            'proxy' => false,
            'timeout' => 15
        ];
        $args = array_merge($default, $args);
        $method = mb_strtolower($args['method']);
        $method_allow = ['get', 'post'];
        if (null === $args['url'] || !in_array($method, $method_allow, true)) {
            return;
        }

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        $options = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $args['user-agent'],
            CURLOPT_REFERER => $args['referer'],
            CURLOPT_TIMEOUT => $args['timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
//            CURLOPT_PROXY => '127.0.0.1:8888'
        ];
        if ($args['proxy']) {
            $proxy = ProxyHelper::getProxy();
            if (isset($proxy) || !empty($proxy['ip'])) {
                $options[CURLOPT_HTTPPROXYTUNNEL] = 1;
                $options[CURLOPT_PROXYUSERPWD] = $proxy['password'];
                $options[CURLOPT_PROXY] = $proxy['ip'];
                $options[CURLOPT_PROXYPORT] = $proxy['port'];
            }
        }

        if (isset($args['cookie'])) {
            $options[CURLOPT_COOKIE] = $args['cookie'];
        }

        $curl = $client->createRequest()
            ->setMethod($args['method'])
            ->setOptions($options);

        if ('post' == $method) {
            $curl->setUrl($args['url'])->setData($args['data']);
        } else {
            $url[] = $args['url'];
            $curl->setUrl(array_merge($url, $args['data']));
        }
        if (!empty($args['headers'])) {
            $curl->setHeaders($args['headers']);
        }
        if (!empty($args['format']))
            $curl->setFormat($args['format']);

        try {
            $content = $curl->send()->getContent();
            return $content;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function send_curl_get_json_data($args = [])
    {
        $default = [
            'method' => 'GET',
            'user-agent' => self::user_agent,
            'url' => null,
            'referer' => '',
            'headers' => null,
            'data' => [],
            'proxy' => false,
            'timeout' => 15
        ];
        $args = array_merge($default, $args);
        $method = mb_strtolower($args['method']);
        $method_allow = ['get', 'post'];
        if (null === $args['url'] || !in_array($method, $method_allow, true)) {
            return;
        }

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        $options = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $args['user-agent'],
            CURLOPT_REFERER => $args['referer'],
            CURLOPT_TIMEOUT => $args['timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
//            CURLOPT_PROXY => '127.0.0.1:8888'
        ];
        if ($args['proxy']) {
            $proxy = ProxyHelper::getProxy();
            if (isset($proxy) || !empty($proxy['ip'])) {
                $options[CURLOPT_HTTPPROXYTUNNEL] = 1;
                $options[CURLOPT_PROXYUSERPWD] = $proxy['password'];
                $options[CURLOPT_PROXY] = $proxy['ip'];
                $options[CURLOPT_PROXYPORT] = $proxy['port'];
            }
        }

        if (isset($args['cookie'])) {
            $options[CURLOPT_COOKIE] = $args['cookie'];
        }

        $curl = $client->createRequest()
//            ->setHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->setMethod($args['method'])
            ->setOptions($options);

        if ('post' == $method) {
            $curl->setUrl($args['url'])->setData($args['data']);
        } else {
            $url[] = $args['url'];
            $curl->setUrl(array_merge($url, $args['data']));
        }
        if (!empty($args['headers'])) {
            $curl->setHeaders($args['headers']);
        }
        if (!empty($args['format']))
            $curl->setFormat($args['format']);

        try {
//            $data = $curl->send()->getData();
            $content = $curl->send()->getContent();
            $data = Json::decode($content);
            return $data;
        } catch (\Exception $e) {
            Yii::error($e->getMessage(), 'curl error');
            if ('yii\base\InvalidArgumentException' == get_class($e) &&
                $e->getCode() == 5) {
                $str = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
                $data = Json::decode($str);
                return $data;
            }

            if (get_class($e) == \yii\httpclient\Exception::class &&
                $args['proxy'] &&
                (strpos($e->getMessage(), '#56') !== false ||   //Received HTTP code 503 from proxy after CONNECT
                    strpos($e->getMessage(), '#28') !== false   //Connection timed out after 5007 milliseconds
                )) {
//                ProxyHelper::fetchAndUpdateProxy(true);
            }

            return false;
        }
    }

    public static function send_curl_with_callback($args = [])
    {
        $default = [
            'method' => 'GET',
            'user-agent' => self::user_agent,
            'url' => null,
            'referer' => 'https://www.google.co.uk',
            'headers' => null,
            'data' => null,
            'proxy' => false,
            'timeout' => 15,
            'callback' => 'callback'
        ];
        $args = array_merge($default, $args);
        $method = mb_strtolower($args['method']);
        $method_allow = ['get', 'post'];
        if (null === $args['url'] || !in_array($method, $method_allow, true)) {
            return;
        }

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        $options = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => $args['user-agent'],
            CURLOPT_REFERER => $args['referer'],
            CURLOPT_TIMEOUT => $args['timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
//            CURLOPT_PROXY => '127.0.0.1:8888'
        ];
        if ($args['proxy']) {
            $proxy = ProxyHelper::getProxy();
            if (isset($proxy) || !empty($proxy['ip'])) {
                $options[CURLOPT_HTTPPROXYTUNNEL] = 1;
                $options[CURLOPT_PROXYUSERPWD] = $proxy['password'];
                $options[CURLOPT_PROXY] = $proxy['ip'];
                $options[CURLOPT_PROXYPORT] = $proxy['port'];
            }
        }

        if (isset($args['cookie'])) {
            $options[CURLOPT_COOKIE] = $args['cookie'];
        }

        $curl = $client->createRequest()
            ->setMethod($args['method'])
            ->setOptions($options);

        if ('post' == $method) {
            $curl->setUrl($args['url'])->setData($args['data']);
        } else {
            $url[] = $args['url'];
            $curl->setUrl(array_merge($url, $args['data']));
        }

        if (!empty($args['headers'])) {
            $curl->setHeaders($args['headers']);
        }
        if (!empty($args['format']))
            $curl->setFormat($args['format']);

        try {
            $content = $curl->send()->getContent();
//            $preg = "/" . $args['callback'] . "\((.*?)\)/U";
            $preg = "/" . $args['callback'] . "\((.[\s\S]*?)\)/U";
            preg_match_all($preg, $content, $m);
            $str = $m[1][0];
            $data = Json::decode($str);
            return $data;
        } catch (\Exception $e) {
            if ('yii\base\InvalidArgumentException' == get_class($e) &&
                $e->getCode() == 5) {
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
                $data = Json::decode($str);
                return $data;
            }

            return false;
        }
    }

    public static function send_curl_get_header($args = [], $header)
    {
        $default = [
            'method' => 'GET',
            'user-agent' => self::user_agent,
            'url' => null,
            'referer' => 'https://www.google.co.uk',
            'headers' => null,
            'data' => [],
            'proxy' => false,
            'timeout' => 15
        ];
        $args = array_merge($default, $args);
        $method = mb_strtolower($args['method']);
        $method_allow = ['get', 'post'];
        if (null === $args['url'] || !in_array($method, $method_allow, true)) {
            return;
        }

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        $options = [
            CURLOPT_USERAGENT => $args['user-agent'],
            CURLOPT_REFERER => $args['referer'],
            CURLOPT_TIMEOUT => $args['timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false
//            CURLOPT_PROXY => '127.0.0.1:8888'
        ];
        if ($args['proxy']) {
            $proxy = ProxyHelper::getProxy();
            if (isset($proxy) || !empty($proxy['ip'])) {
                $options[CURLOPT_HTTPPROXYTUNNEL] = 1;
                $options[CURLOPT_PROXYUSERPWD] = $proxy['password'];
                $options[CURLOPT_PROXY] = $proxy['ip'];
                $options[CURLOPT_PROXYPORT] = $proxy['port'];
            }
        }

        if (isset($args['cookie'])) {
            $options[CURLOPT_COOKIE] = $args['cookie'];
        }

        $curl = $client->createRequest()
            ->setHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->setMethod($args['method'])
            ->setOptions($options);

        if ('post' == $method) {
            $curl->setUrl($args['url'])->setData($args['data']);
        } else {
            $url[] = $args['url'];
            $curl->setUrl(array_merge($url, $args['data']));
        }
        if (!empty($args['headers'])) {
            $curl->setHeaders($args['headers']);
        }
        if (!empty($args['format']))
            $curl->setFormat($args['format']);

        try {
            $headers = $curl->send()->getHeaders();
            $data = $headers[$header];
            return $data;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function send_curl_get_cookies($args = [])
    {
        $default = [
            'method' => 'GET',
            'user-agent' => self::user_agent,
            'url' => null,
            'referer' => 'https://www.google.co.uk',
            'headers' => null,
            'data' => [],
            'proxy' => false,
            'timeout' => 15
        ];
        $args = array_merge($default, $args);
        $method = mb_strtolower($args['method']);
        $method_allow = ['get', 'post'];
        if (null === $args['url'] || !in_array($method, $method_allow, true)) {
            return;
        }

        $client = new Client([
            'transport' => 'yii\httpclient\CurlTransport',
        ]);

        $options = [
            CURLOPT_USERAGENT => $args['user-agent'],
            CURLOPT_REFERER => $args['referer'],
            CURLOPT_TIMEOUT => $args['timeout'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false
//            CURLOPT_PROXY => '127.0.0.1:8888'
        ];
        if ($args['proxy']) {
            $proxy = ProxyHelper::getProxy();
            if (isset($proxy) || !empty($proxy['ip'])) {
                $options[CURLOPT_HTTPPROXYTUNNEL] = 1;
                $options[CURLOPT_PROXYUSERPWD] = $proxy['password'];
                $options[CURLOPT_PROXY] = $proxy['ip'];
                $options[CURLOPT_PROXYPORT] = $proxy['port'];
            }
        }

        if (isset($args['cookie'])) {
            $options[CURLOPT_COOKIE] = $args['cookie'];
        }

        $curl = $client->createRequest()
            ->setMethod($args['method'])
            ->setOptions($options);

        if ('post' == $method) {
            $curl->setUrl($args['url'])->setData($args['data']);
        } else {
            $url[] = $args['url'];
            $curl->setUrl(array_merge($url, $args['data']));
        }
        if (!empty($args['headers'])) {
            $curl->setHeaders($args['headers']);
        }
        if (!empty($args['format']))
            $curl->setFormat($args['format']);

        try {
            $cookies = $curl->send()->getCookies()->toArray();
            return $cookies;
        } catch (\Exception $e) {


            return false;
        }
    }
}