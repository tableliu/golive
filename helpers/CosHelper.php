<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/19
 * Time: 17:22
 */

namespace app\helpers;


use Qcloud\Cos\Client;
use QCloud\COSSTS\Sts;
use Yii;

class CosHelper
{
    /**
     * @param $user_id
     * @param $type
     * @param $exts
     * @return array
     * @throws \yii\base\Exception
     */
    private static function getUrlAndFileName($user_id, $type, $exts): array
    {
        $bucket = Yii::$app->params['COS_BUCKET'][$type]['name'];
        $region = Yii::$app->params['COS_BUCKET'][$type]['region'];
        $protocol = Yii::$app->request->getIsSecureConnection() === 'https:' ? 'https:' : 'http:';
        $folder = date("Y-m-d");
        $files = [];
        foreach ($exts as $ext) {
            $file_name = $user_id . '_' . Yii::$app->security->generateRandomString(16);
            $path_name = '/' . $folder . '/' . $file_name . '.' . $ext;
            $full_path = $protocol . '//' . $bucket . '.cos.' . $region . '.myqcloud.com' . $path_name;
            $files[] = [
                'path_name' => $path_name,
                'bucket' => $bucket,
                'region' => $region,
                'url' => $full_path,
            ];
        }

        return array($folder, $files);
    }


    /**
     * @param $user_id
     * @param $type
     * @param $folder
     * @return array|mixed|null|string
     * @throws \Exception
     */
    private function getTempKeys($user_id, $type, $folder = null)
    {
        $sts = new Sts();
        $config = array(
            'url' => Yii::$app->params['COS_URL'],
            'domain' => Yii::$app->params['COS_DOMAIN'],
            'proxy' => '',
            'secretId' => Yii::$app->params['COS_SECRET_ID'], // 固定密钥
            'secretKey' => Yii::$app->params['COS_SECRET_KEY'], // 固定密钥
            'bucket' => Yii::$app->params['COS_BUCKET'][$type]['name'], // 换成你的 bucket
            'region' => Yii::$app->params['COS_BUCKET'][$type]['region'], // 换成 bucket 所在园区
            'durationSeconds' => 1800, // 密钥有效期
            'allowPrefix' => $folder . '/' . $user_id . '_*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => array(
                'name/cos:GetObject',
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload',
                "name/cos:AbortMultipartUpload",

            )
        );

        // 获取临时密钥，计算签名
        $tempKeys = $sts->getTempKeys($config);
        return $tempKeys;
    }

    /**
     * @param $user_id integer
     * @param $type string
     * @param $exts array
     * @return array
     * @throws \yii\base\Exception
     */
    public static function getTempKeysBulk($user_id, $type, $exts)
    {
        list($folder, $files) = CosHelper::getUrlAndFileName($user_id, $type, $exts);
        $tempKeys = CosHelper::getTempKeys($user_id, $type, $folder);
        return [
            'files' => $files,
            'tmp_keys' => $tempKeys
        ];
    }


    public static function deleteCosObject($key, $cos_back_key)
    {
        $cosClient = new Client(
            array(
                'region' => Yii::$app->params['COS_BUCKET'][$cos_back_key]['region'],
                'schema' => 'https',
                'credentials' => array(
                    'secretId' => Yii::$app->params['COS_SECRET_ID'],
                    'secretKey' => Yii::$app->params['COS_SECRET_KEY'])));
        $bucket = Yii::$app->params['COS_BUCKET'][$cos_back_key]['name'];
        try {
            $headObjectResult = $cosClient->headObject(array(
                'Bucket' => $bucket,
                'Key' => $key
            ));
            if ($headObjectResult)
                $cosClient->deleteObject(array(
                    'Bucket' => $bucket,
                    'Key' => $key
                ));
            return;
        } catch (\Exception $e) {
            return;
        }


    }
}