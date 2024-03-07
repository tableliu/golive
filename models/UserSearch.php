<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
/**
 * UserSearch represents the model behind the search form about `app\models\users\User`.
 */
class UserSearch extends \amnah\yii2\user\models\search\UserSearch
{
    public function search($params)
    {



        // get models
        $user = new User;
        $profile =new Profile;
        $userTable = $user::tableName();
        $profileTable = $profile::tableName();

        $query = User::find()->select(['user.id as id', 'username', 'email', 'wx_user_name', 'status', 'wx_user_name', 'profile.full_name']);

        $query->joinWith(['profile' =>
            function ($query) use ($profileTable) {
                $query->from(['profile' => $profileTable]);
            }]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => (isset($params['iipsearch']) && !empty($params['iipsearch'])) ? '' : (isset($params['per-page']) ? $params['per-page'] : Yii::$app->params['GridView.pagination.pageSize']),
            ],
            'sort' => [
                'defaultOrder' => [
                   'id' => SORT_DESC,
                ]
            ],
        ]);

        // enable sorting for the related columns

        $addSortAttributes = [""];
        foreach ($addSortAttributes as $addSortAttribute) {
            $dataProvider->sort->attributes[$addSortAttribute] = [
                'asc' => [$addSortAttribute => SORT_ASC],
                'desc' => [$addSortAttribute => SORT_DESC],
            ];
        }
        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }
        $query->andFilterWhere([
            "{$userTable}.id" => $this->id,
            'role_id' => $this->role_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'username', $this->username])
            ->andFilterWhere(['like', 'logged_in_ip', $this->logged_in_ip])
            ->andFilterWhere(['like', "full_name", $this->logged_in_ip]);

        if (isset($this->created_at) && !empty($this->created_at)) {
            $date = explode(" - ", $this->created_at);
            $query->andFilterWhere(['between', User::tableName() . '.created_at', trim($date[0]), trim($date[1])]);
        }

        if (isset($this->logged_in_at) && !empty($this->logged_in_at)) {
            $date = explode(" - ", $this->logged_in_at);
            $query->andFilterWhere(['between', User::tableName() . '.logged_in_at', strtotime(trim($date[0])), strtotime(trim($date[1]))]);
        }

        if (isset($this->banned_at) && $this->banned_at != '') {
            if ($this->banned_at == 0) {
                $query->andWhere(['not', ['banned_at' => null]]);
            } elseif ($this->banned_at == 1) {
                $query->andWhere(['banned_at' => null]);
            }
        }
        return $dataProvider;
    }
}
