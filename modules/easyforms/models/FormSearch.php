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

namespace app\modules\easyforms\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\easyforms\models\Form;

/**
 * FormSearch represents the model behind the search form about `app\models\Form`.
 */
class FormSearch extends Form
{

    public $author;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'save', 'schedule', 'total_limit', 'ip_limit',
                'resume', 'autocomplete', 'novalidate', 'analytics', 'honeypot', 'recaptcha',
            ], 'integer'],
            [['password', 'language'], 'string'],
            [['name', 'message', 'language', 'author', 'updated_at'], 'safe'],
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

        $query = Form::find();

        // Important: join the query with our author relation (Ref: User model)
        $query->joinWith(['author']);
       //print_r($query->createCommand()->sql);die();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
//            'pagination' => [
//                'pageSize' => Yii::$app->user->preferences->get('GridView.pagination.pageSize'),
//            ],
            'sort' => [
                'defaultOrder' => [
                    'updated_at' => SORT_DESC,
                ]
            ],
        ]);

        // Search form by User username
        $dataProvider->sort->attributes['author'] = [
            'asc' => ['{{%user}}.username' => SORT_ASC],
            'desc' => ['{{%user}}.username' => SORT_DESC],
        ];

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            '{{%form}}.status' => $this->status,
            'schedule' => $this->schedule,
            'total_limit' => $this->total_limit,
            'ip_limit' => $this->ip_limit,
            'save' => $this->save,
            'resume' => $this->resume,
            'autocomplete' => $this->autocomplete,
            'novalidate' => $this->novalidate,
            'analytics' => $this->analytics,
            'honeypot' => $this->honeypot,
            'recaptcha' => $this->recaptcha,
            'message' => $this->message,
        ]);

        if (isset($this->updated_at) && !empty($this->updated_at)) {
            list($start, $end) = explode(" - ", $this->updated_at);
            $startAt = strtotime(trim($start));
            // Add +1 day to the endAt
            $endAt = strtotime(trim($end)) + (24 * 60 * 60);
            $query->andFilterWhere(['between', '{{%form}}.updated_at', $startAt, $endAt]);
        }

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'message', $this->message])
            ->andFilterWhere(['like', 'language', $this->language])
            ->andFilterWhere(['like', '{{%user}}.username', $this->author]);

//        if (!empty(Yii::$app->user) && !Yii::$app->user->can("edit_own_content")) {
//            // If Basic User
//            // Add 'assigned to' filter
//            $formIds = Yii::$app->user->getAssignedFormIds();
//            $formIds = count($formIds) > 0 ? $formIds : 0; // Important restriction
//            $query->andFilterWhere(['{{%form}}.id' => $formIds]);
//        } elseif (!empty(Yii::$app->user) && !Yii::$app->user->can("admin")) {
//            // If Advanced User
//            // Add 'created by' filter
//            $query->andFilterWhere(['{{%form}}.created_by' => Yii::$app->user->id]);
        //       }
        // $query->andFilterWhere(['{{%form}}.created_by' => Yii::$app->user->id]);

        return $dataProvider;
    }
}
