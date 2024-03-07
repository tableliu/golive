<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class LiveRoomSearch extends \yii\db\ActiveRecord
{
    public function getRoomList($params)
    {
        $query = (new Query())
            ->select(['room.id as id', 'room.created_at', 'room.closed_at', 'room.status', 'room.streamer_id', 'count(room_user.id) as user_count', 'full_name'])
            ->from(LiveRoom::tableName() . ' room')
            ->innerJoin(LiveRoomUser::tableName() . ' room_user', 'room_user.room_id = room.id')
            ->leftJoin(Profile::tableName() . ' profile', 'profile.user_id = room.streamer_id')
            ->groupBy('room.id')
            ->orderBy('room.id desc');

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $query->where(['streamer_id' => $params['user_id']]);
        }
        if (isset($params['time']) && !empty($params['time'])) {
            if (strstr($params['time'], ' - ') !== false) {
                $date = explode(" - ", $params['time']);
                $query->andWhere(['between', "date_format({{%room}}.created_at,'%Y-%m-%d')", trim($date[0]), trim($date[1])]);
            } else {
                $query->andWhere(["date_format({{%room}}.created_at,'%Y-%m-%d')" => trim($params['time'])]);
            }

        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => (isset($params['iipsearch']) && !empty($params['iipsearch'])) ? '' : (isset($params['per-page']) ? $params['per-page'] : Yii::$app->params['GridView.pagination.pageSize']),
            ],
        ]);
        return $dataProvider;
    }

}
