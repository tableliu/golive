<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/3/30
 * Time: 15:43
 */

namespace app\components\socket\Wamp;

use Yii;
use yii\base\Component;

class WampCloseApplication extends WampOpenApplication
{
    public function run()
    {
        try {
            $exitStatus = $this->handleRequest(null);
            return $exitStatus;
        } catch (\Exception $e) {
            $this->end(1);
            return 1;
        }
    }

    public function getDb()
    {
        return $this->settings->getCurrentDB();
    }

    public function handleRequest($request)
    {
        // must login
        if ($this->user->offline() === false) {
            printf("User " . $this->user->id . " Close Failed \r\n");
            return 1;
        } else {
            printf("User " . $this->user->id . " Close Success \r\n");
            return 0;
        }

    }

}