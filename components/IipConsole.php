<?php

namespace app\components;
use yii;
use yii\console\Application;
use yii\di\Instance;

class IipConsole extends Application
{
    public function getDb()
    {
        return $this->settings->getCurrentDB();
    }
}