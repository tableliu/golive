<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2019/9/27
 * Time: 14:45
 */

namespace app\components;


use yii\debug\Module;

class IipDebugModule extends Module
{
    protected function corePanels()
    {

        return [
            'config' => ['class' => 'yii\debug\panels\ConfigPanel'],
            'request' => ['class' => 'yii\debug\panels\RequestPanel'],
            'log' => ['class' => 'yii\debug\panels\LogPanel'],
            'profiling' => ['class' => 'yii\debug\panels\ProfilingPanel'],
            'db' => ['class' => 'yii\debug\panels\DbPanel'],
            'assets' => ['class' => 'yii\debug\panels\AssetPanel'],
            'mail' => ['class' => 'yii\debug\panels\MailPanel'],
            'timeline' => ['class' => 'yii\debug\panels\TimelinePanel'],
//            'user' => ['class' => 'yii\debug\panels\UserPanel'],
            'router' => ['class' => 'yii\debug\panels\RouterPanel']
        ];
    }
}