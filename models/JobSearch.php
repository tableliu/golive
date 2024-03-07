<?php

namespace app\models;


use app\modules\easyforms\models\Form;
use app\modules\easyforms\models\FormSubmission;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


/**
 * VideoJobSearch represents the model behind the search form of `app\models\VideoJob`.
 */
class JobSearch extends VideoRecorder
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'safe'],
            [['updated_by', 'created_by','job_id','size'], 'integer'],
            [['extension','name'], 'string', 'max' => 255],
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
    public function search($params)
    {
        $query = VideoRecorder::find();
        $query->joinWith(['video']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if (isset($params['content']) && !empty($params['content'])) {
             $query->orWhere(['like','{{%video_job}}.name',$params['content']]);

        }
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        if (isset($params['job_id']) && !empty($params['job_id'])) {
                $query->andWhere(['job_id' => $params['job_id']]);
        }

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $query->andWhere(['{{%video_record}}.created_by' => $params['user_id']]);

        }

        if (isset($params['time']) && !empty($params['time'])){
            if (strstr($params['time'], ' - ') !== false) {
                $date = explode(" - ", $params['time']);
                $query->andWhere(['between', "date_format({{%video_record}}.created_at,'%Y-%m-%d')", trim($date[0]), trim($date[1])]);
            } else {
                $query->andWhere(["date_format({{%video_record}}.created_at,'%Y-%m-%d')" => $params['time']]);
            }

        }
        //print_r($query->createCommand()->sql);die();
        return $dataProvider;
    }

    public function searchForm($params){


        $query = FormSubmission::find();
        $query->select(['form_submission.*','form.name as name']);
        $query->joinWith(['form']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
            ]);


        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if (isset($params['content']) && !empty($params['content'])) {
            $query->orWhere(['like','name',$params['content']]);

        }
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        if (isset($params['job_id']) && !empty($params['job_id'])) {
            $query->andWhere(['form_id' => $params['job_id']]);
        }

        if (isset($params['user_id']) && !empty($params['user_id'])) {
            $query->andWhere(['{{%form_submission}}.created_by' => $params['user_id']]);

        }

        if (isset($params['time']) && !empty($params['time'])){
            if (strstr($params['time'], ' - ') !== false) {
                $date = explode(" - ", $params['time']);
                $query->andWhere(['between', "FROM_UNIXTIME({{%form_submission}}.created_at, '%Y-%m-%d ' ) ", trim($date[0]), trim($date[1])]);
            } else {
                $query->andWhere(["FROM_UNIXTIME({{%form_submission}}.created_at, '%Y-%m-%d ' ) " => $params['time']]);
            }

        }
        return $dataProvider;

        }


}
