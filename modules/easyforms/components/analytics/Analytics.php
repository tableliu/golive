<?php
/**
 * Copyright (C) Baluart.COM - All Rights Reserved
 *
 * @since 1.0
 * @author Balu
 * @copyright Copyright (c) 2015 - 2019 Baluart.COM
 * @license http://codecanyon.net/licenses/faq Envato marketplace licenses
 * @link http://easyforms.baluart.com/ Easy Forms
 */

namespace app\modules\easyforms\components\analytics;

use yii\base\Component;
use app\modules\easyforms\components\analytics\collector\Collector;
use app\modules\easyforms\components\analytics\enricher\Enricher;
use app\modules\easyforms\components\analytics\storage\Storage;
use app\components\analytics\modeler\Modeler;
use app\components\analytics\report\Report;

/**
 * Class Analytics
 * @package app\components\analytics
 */
class Analytics extends Component
{

    public static function collect()
    {
        $collector = new Collector();
        $rawData = $collector->getData();
        $enricher = new Enricher();
        $enricher->setData($rawData);
        $enricher->process();
        $enrichedData = $enricher->getData();
        $storage = new Storage();
        $storage->save($enrichedData);
    }

    public static function aggregate()
    {
        $modeler = new Modeler();
        $modeler->run();
    }

    public static function report()
    {
        return new Report();
    }
}
