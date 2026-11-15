<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台比赛列表搜索模型。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class GameSearch extends Model
{
    public $q;
    public $tournamentId;
    public $teamId;
    public $stage;
    public $status;
    public $year;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q', 'stage'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['stage', 'in', 'range' => Game::STAGES],
            ['status', 'in', 'range' => array_keys(Game::statusOptions())],
            ['year', 'integer', 'min' => 1900, 'max' => 2100],
            ['tournamentId', 'integer', 'min' => 1],
            ['tournamentId', 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id'],
            ['teamId', 'integer', 'min' => 1],
            ['teamId', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'q' => '球队或场地',
            'tournamentId' => '届次',
            'teamId' => '球队',
            'stage' => '比赛阶段',
            'status' => '比赛状态',
            'year' => '年份',
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Game::find()
            ->alias('g')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = g.tournament_id')
            ->innerJoin(['h' => '{{%team}}'], 'h.id = g.home_team_id')
            ->innerJoin(['a' => '{{%team}}'], 'a.id = g.away_team_id')
            ->select(['g.*']);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['match_time' => SORT_DESC, 'id' => SORT_DESC],
                'attributes' => [
                    'id' => [
                        'asc' => ['g.id' => SORT_ASC],
                        'desc' => ['g.id' => SORT_DESC],
                    ],
                    'match_time' => [
                        'asc' => ['g.match_time' => SORT_ASC],
                        'desc' => ['g.match_time' => SORT_DESC],
                    ],
                    'stage' => [
                        'asc' => ['g.stage' => SORT_ASC],
                        'desc' => ['g.stage' => SORT_DESC],
                    ],
                    'status' => [
                        'asc' => ['g.status' => SORT_ASC],
                        'desc' => ['g.status' => SORT_DESC],
                    ],
                    'venue' => [
                        'asc' => ['g.venue' => SORT_ASC],
                        'desc' => ['g.venue' => SORT_DESC],
                    ],
                    'tournament_id' => [
                        'asc' => ['t.year' => SORT_ASC],
                        'desc' => ['t.year' => SORT_DESC],
                    ],
                    'home_team_id' => [
                        'asc' => ['h.name' => SORT_ASC],
                        'desc' => ['h.name' => SORT_DESC],
                    ],
                    'away_team_id' => [
                        'asc' => ['a.name' => SORT_ASC],
                        'desc' => ['a.name' => SORT_DESC],
                    ],
                    'tournament_year' => [
                        'asc' => ['t.year' => SORT_ASC],
                        'desc' => ['t.year' => SORT_DESC],
                    ],
                    'home_team_name' => [
                        'asc' => ['h.name' => SORT_ASC],
                        'desc' => ['h.name' => SORT_DESC],
                    ],
                    'away_team_name' => [
                        'asc' => ['a.name' => SORT_ASC],
                        'desc' => ['a.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->andWhere(['g.id' => -1]);
            return $provider;
        }

        $query->andFilterWhere(['g.tournament_id' => $this->tournamentId]);
        $query->andFilterWhere(['t.year' => $this->year]);
        $query->andFilterWhere(['g.stage' => $this->stage]);
        $query->andFilterWhere(['g.status' => $this->status]);
        if ($this->teamId !== null && $this->teamId !== '') {
            $query->andWhere(['or',
                ['g.home_team_id' => $this->teamId],
                ['g.away_team_id' => $this->teamId],
            ]);
        }
        $query->andFilterWhere(['or',
            ['like', 'h.name', $this->q],
            ['like', 'a.name', $this->q],
            ['like', 'g.venue', $this->q],
        ]);

        return $provider;
    }
}
