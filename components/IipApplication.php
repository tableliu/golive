<?php

namespace app\components;

use yii\web\Application;

class IipApplication extends Application
{
    public function getDb()
    {
        return $this->settings->getCurrentDB();
    }
}