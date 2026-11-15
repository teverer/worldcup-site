<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛名单搜索模型。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class SquadSearch extends Model
{
    public $q;
    public $tournamentId;
    public $teamId;
    public $position;
    public $shirtNumber;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q', 'position'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['position', 'string', 'max' => 20],
            ['tournamentId', 'integer', 'min' => 1],
            ['tournamentId', 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id'],
            ['teamId', 'integer', 'min' => 1],
            ['teamId', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id'],
            ['shirtNumber', 'integer', 'min' => 1, 'max' => 99],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'q' => '球员或球队',
            'tournamentId' => '届次',
            'teamId' => '球队',
            'position' => '位置',
            'shirtNumber' => '球衣号',
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Squad::find()
            ->alias('s')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = s.tournament_id')
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = s.team_id')
            ->innerJoin(['p' => '{{%player}}'], 'p.id = s.player_id')
            ->select(['s.*']);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => [
                    'tournament_year' => SORT_DESC,
                    'team_name' => SORT_ASC,
                    'shirt_number' => SORT_ASC,
                ],
                'attributes' => [
                    'id' => [
                        'asc' => ['s.id' => SORT_ASC],
                        'desc' => ['s.id' => SORT_DESC],
                    ],
                    'shirt_number' => [
                        'asc' => ['s.shirt_number' => SORT_ASC],
                        'desc' => ['s.shirt_number' => SORT_DESC],
                    ],
                    'position' => [
                        'asc' => ['s.position' => SORT_ASC],
                        'desc' => ['s.position' => SORT_DESC],
                    ],
                    'tournament_id' => [
                        'asc' => ['t.year' => SORT_ASC],
                        'desc' => ['t.year' => SORT_DESC],
                    ],
                    'team_id' => [
                        'asc' => ['tm.name' => SORT_ASC],
                        'desc' => ['tm.name' => SORT_DESC],
                    ],
                    'player_id' => [
                        'asc' => ['p.name' => SORT_ASC],
                        'desc' => ['p.name' => SORT_DESC],
                    ],
                    'tournament_year' => [
                        'asc' => ['t.year' => SORT_ASC],
                        'desc' => ['t.year' => SORT_DESC],
                    ],
                    'team_name' => [
                        'asc' => ['tm.name' => SORT_ASC],
                        'desc' => ['tm.name' => SORT_DESC],
                    ],
                    'player_name' => [
                        'asc' => ['p.name' => SORT_ASC],
                        'desc' => ['p.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->andWhere(['s.id' => -1]);
            return $provider;
        }

        $query->andFilterWhere(['s.tournament_id' => $this->tournamentId]);
        $query->andFilterWhere(['s.team_id' => $this->teamId]);
        $query->andFilterWhere(['like', 's.position', $this->position]);
        $query->andFilterWhere(['s.shirt_number' => $this->shirtNumber]);
        $query->andFilterWhere(['or',
            ['like', 'p.name', $this->q],
            ['like', 'tm.name', $this->q],
        ]);

        return $provider;
    }
}
