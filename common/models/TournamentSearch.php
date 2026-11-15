<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台届次列表搜索模型。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class TournamentSearch extends Model
{
    public $q;
    public $year;
    public $hostCountry;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q', 'hostCountry'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['hostCountry', 'string', 'max' => 100],
            ['year', 'integer', 'min' => 1900, 'max' => 2100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'q' => '关键词',
            'year' => '年份',
            'hostCountry' => '东道主',
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Tournament::find();
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['year' => SORT_DESC],
                'attributes' => ['id', 'year', 'name', 'host_country', 'team_count',
                    'win_points', 'start_date', 'end_date'],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->andWhere(['id' => -1]);
            return $provider;
        }

        $query->andFilterWhere(['year' => $this->year]);
        $query->andFilterWhere(['like', 'host_country', $this->hostCountry]);
        $query->andFilterWhere(['or',
            ['like', 'name', $this->q],
            ['like', 'host_country', $this->q],
            ['like', 'format', $this->q],
        ]);

        return $provider;
    }
}
