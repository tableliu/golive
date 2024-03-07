<?php
/**
 * Created by PhpStorm.
 * User: Liu
 * Date: 2020/6/5
 * Time: 12:39
 */

namespace app\components\behaviors;

use app\models\LiveRoom;
use app\models\LiveRoomUser;
use app\models\Online;
use yii\base\InvalidCallException;
use yii\db\ActiveRecord;

class LiveRoomUserBehavior extends \yii\base\Behavior
{
    public function attach($owner)
    {
        if ($owner instanceof Online)
            parent::attach($owner);
        else
            throw new InvalidCallException('Type must be Online');
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    public function afterDelete()
    {
        $online = $this->owner;
        if (!isset($online))
            return;
        $room_users = LiveRoomUser::find()->where(
            [
                'user_wamp_session_id' => $online->wamp_session_id,
                'status' => 1
            ])
            ->all();
        /* @var $room_user LiveRoomUser */
        foreach ($room_users as $room_user) {
            LiveRoom::userLeave($room_user->room_id, $online);
        }
    }

}