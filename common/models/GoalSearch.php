<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台进球记录搜索模型。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class GoalSearch extends Model
{
    public $q;
    public $tournamentId;
    public $teamId;
    public $gameId;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['q', 'trim'],
            ['q', 'string', 'max' => 100],
            ['tournamentId', 'integer', 'min' => 1],
            ['tournamentId', 'exist', 'targetClass' => Tournament::class, 'targetAttribute' => 'id'],
            ['teamId', 'integer', 'min' => 1],
            ['teamId', 'exist', 'targetClass' => Team::class, 'targetAttribute' => 'id'],
            ['gameId', 'integer', 'min' => 1],
            ['gameId', 'exist', 'targetClass' => Game::class, 'targetAttribute' => 'id'],
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
            'gameId' => '比赛',
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Goal::find()
            ->alias('go')
            ->innerJoin(['m' => '{{%game}}'], 'm.id = go.game_id')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = m.tournament_id')
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = go.team_id')
            ->innerJoin(['p' => '{{%player}}'], 'p.id = go.player_id')
            ->select(['go.*']);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['match_time' => SORT_DESC, 'minute' => SORT_ASC, 'id' => SORT_ASC],
                'attributes' => [
                    'id' => [
                        'asc' => ['go.id' => SORT_ASC],
                        'desc' => ['go.id' => SORT_DESC],
                    ],
                    'minute' => [
                        'asc' => ['go.minute' => SORT_ASC],
                        'desc' => ['go.minute' => SORT_DESC],
                    ],
                    'is_penalty' => [
                        'asc' => ['go.is_penalty' => SORT_ASC],
                        'desc' => ['go.is_penalty' => SORT_DESC],
                    ],
                    'is_own_goal' => [
                        'asc' => ['go.is_own_goal' => SORT_ASC],
                        'desc' => ['go.is_own_goal' => SORT_DESC],
                    ],
                    'is_shootout' => [
                        'asc' => ['go.is_shootout' => SORT_ASC],
                        'desc' => ['go.is_shootout' => SORT_DESC],
                    ],
                    'game_id' => [
                        'asc' => ['go.game_id' => SORT_ASC],
                        'desc' => ['go.game_id' => SORT_DESC],
                    ],
                    'team_id' => [
                        'asc' => ['tm.name' => SORT_ASC],
                        'desc' => ['tm.name' => SORT_DESC],
                    ],
                    'player_id' => [
                        'asc' => ['p.name' => SORT_ASC],
                        'desc' => ['p.name' => SORT_DESC],
                    ],
                    'match_time' => [
                        'asc' => ['m.match_time' => SORT_ASC],
                        'desc' => ['m.match_time' => SORT_DESC],
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
            $query->andWhere(['go.id' => -1]);
            return $provider;
        }

        $query->andFilterWhere(['m.tournament_id' => $this->tournamentId]);
        $query->andFilterWhere(['go.team_id' => $this->teamId]);
        $query->andFilterWhere(['go.game_id' => $this->gameId]);
        $query->andFilterWhere(['or',
            ['like', 'p.name', $this->q],
            ['like', 'tm.name', $this->q],
        ]);

        return $provider;
    }
}
