<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛队列表搜索模型。
 */

namespace common\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

class TournamentTeamSearch extends Model
{
    public $q;
    public $tournamentId;
    public $teamId;
    public $groupName;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q', 'groupName'], 'trim'],
            ['q', 'string', 'max' => 100],
            ['groupName', 'string', 'max' => 10],
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
            'q' => '关键词',
            'tournamentId' => '届次',
            'teamId' => '球队',
            'groupName' => '小组',
        ];
    }

    /**
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = TournamentTeam::find()
            ->alias('tt')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = tt.tournament_id')
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = tt.team_id')
            ->select(['tt.*']);

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['tournament_year' => SORT_DESC, 'id' => SORT_ASC],
                'attributes' => [
                    'id' => [
                        'asc' => ['tt.id' => SORT_ASC],
                        'desc' => ['tt.id' => SORT_DESC],
                    ],
                    'group_name' => [
                        'asc' => ['tt.group_name' => SORT_ASC],
                        'desc' => ['tt.group_name' => SORT_DESC],
                    ],
                    'coach' => [
                        'asc' => ['tt.coach' => SORT_ASC],
                        'desc' => ['tt.coach' => SORT_DESC],
                    ],
                    'fifa_rank' => [
                        'asc' => ['tt.fifa_rank' => SORT_ASC],
                        'desc' => ['tt.fifa_rank' => SORT_DESC],
                    ],
                    'final_rank' => [
                        'asc' => ['tt.final_rank' => SORT_ASC],
                        'desc' => ['tt.final_rank' => SORT_DESC],
                    ],
                    'tournament_id' => [
                        'asc' => ['t.year' => SORT_ASC],
                        'desc' => ['t.year' => SORT_DESC],
                    ],
                    'team_id' => [
                        'asc' => ['tm.name' => SORT_ASC],
                        'desc' => ['tm.name' => SORT_DESC],
                    ],
                    'tournament_year' => [
                        'asc' => ['t.year' => SORT_ASC],
                        'desc' => ['t.year' => SORT_DESC],
                    ],
                    'team_name' => [
                        'asc' => ['tm.name' => SORT_ASC],
                        'desc' => ['tm.name' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->andWhere(['tt.id' => -1]);
            return $provider;
        }

        $query->andFilterWhere(['tt.tournament_id' => $this->tournamentId]);
        $query->andFilterWhere(['tt.team_id' => $this->teamId]);
        $query->andFilterWhere(['like', 'tt.group_name', $this->groupName]);
        $query->andFilterWhere(['or',
            ['like', 't.name', $this->q],
            ['like', 'tm.name', $this->q],
            ['like', 'tt.coach', $this->q],
        ]);

        return $provider;
    }
}
