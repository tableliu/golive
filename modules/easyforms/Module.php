<?php

namespace app\modules\easyforms;

use Yii;
use yii\web\Response;

class Module extends \yii\base\Module
{
    public $alias = "@form";
    public $emailViewPath = "@form/mail";
    public $bootstrap;
   // public $language;
    public function init()
    {

        parent::init();

        // set alias
        $this->setAliases([
            $this->alias => __DIR__,
        ]);

        Yii::configure($this, require __DIR__ . '/config/web.php');

        //print_r(Yii::$app->i18n->translations);die();
        if (empty(Yii::$app->i18n->translations['form'])) {
            Yii::$app->i18n->translations['form'] = [
                'class' => 'yii\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@app/modules/easyforms/messages',
                //'forceTranslation' => true,
            ];
           //print_r(Yii::$app->i18n->translations);die();
        }
    }


}

?>