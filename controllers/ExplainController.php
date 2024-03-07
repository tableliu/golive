<?php

namespace app\controllers;

use app\common\IIPActiveController;
use app\common\RestResult;
use app\common\Consts;
use Yii;
use yii\filters\VerbFilter;


/**
 * DayReportController implements the CRUD actions for DayReport model.
 */
class ExplainController extends IIPActiveController
{


    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::className(),
            'actions' => [
            ],
        ];
        return $behaviors;
    }

    public function actionShowDoc()
    {
        $path = Yii::getAlias('@webroot') . '/';
        $pdfDir = "upload/doc/document.pdf";
        $pdf = $path . $pdfDir;
        if (!file_exists($pdf)) {
            return $result = Yii::createObject(
                [
                    'class' => RestResult::className(),
                    'code' => Consts::REST_FILE_NOT_EXIST,
                    'data' => "",
                    'msg' => Yii::t('app', 'not exist'),
                ]);
        }
        $file = fopen($pdf, "r");
        Header("Content-type: application/pdf");
        echo fread($file, filesize($pdf));
        fclose($file);

    }

}
