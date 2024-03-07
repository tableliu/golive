<?php

namespace app\modules\onsite;

use Yii;

class Module extends \app\components\socket\rpc\Module
{

    public $alias = "@onsite";

    public function init()
    {
        parent::init();

        // set alias
        $this->setAliases([
            $this->alias => __DIR__,
        ]);

        Yii::configure($this, require __DIR__ . '/config/web.php');
        // set up i8n
        if (empty(Yii::$app->i18n->translations['onsite'])) {
            Yii::$app->i18n->translations['onsite'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@onsite/messages'
            ];
        }

        //user
        \Yii::$app->user->enableSession = false;
    }
}

?>