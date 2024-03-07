<?php

namespace app\models;

use app\components\Settings;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Project;
use yii\db\Query;

/**
 * ProjectSearch represents the model behind the search form of `app\models\Project`.
 */
class ProjectSearch extends Project
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'pid', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($user_id)
    {

        return Project::find()
            ->from(Project::tableName() . ' A')
            ->select(['A.pid', 'name'])
            ->leftJoin(ProjectUser::tableName() . ' B', 'B.pid = A.id')
            ->where(["user_id" => $user_id])
            ->asArray()
            ->all();

    }

    public function projectIndex()
    {
        return Project::find()
            ->from(Project::tableName() . ' A')
            ->select(['id', 'pid', 'name'])
            ->asArray()
            ->all();

    }
}
