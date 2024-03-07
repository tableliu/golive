<?php

namespace app\components\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;

class LiveRoomStatusBehavior extends Behavior
{

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
        ];
    }


    public function beforeUpdate($event)
    {
        $this->changeClosedAt($event);

    }

    public function beforeInsert($event)
    {
        $this->changeClosedAt($event);

    }


    public function changeClosedAt($event)
    {

        $status = $this->owner->status;
        if ($status == 1)
            return $this->owner->closed_at = null;
        $old_status = $event->sender->oldAttributes['status'];
        if ($status == 0 && $old_status == 1)
            return $this->owner->closed_at = date('Y-m-d H:i:s', time());
    }


}