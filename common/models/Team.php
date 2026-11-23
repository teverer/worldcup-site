<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球队资料与跨届名单查询。
 */

namespace common\models;

use common\validators\AssetPathValidator;
use yii\db\ActiveRecord;
use yii\db\Query;

class Team extends ActiveRecord {
    // 与已有数据一致:美加墨等中北美球队统一记作「中北美」。
    const CONTINENTS = ['亚洲', '欧洲', '非洲', '中北美', '南美洲', '大洋洲'];

    public static function tableName() {
        return '{{%team}}';
    }

    public function rules() {
        return [
            [['name', 'code', 'continent', 'flag', 'logo', 'intro'], 'trim'],
            [['code', 'continent', 'flag', 'logo', 'intro'], 'default', 'value' => null],
            ['name', 'required'],
            ['name', 'string', 'max' => 50],
            ['name', 'unique'],
            ['code', 'filter', 'filter' => function ($value) { return $value === null ? null : strtoupper($value); }],
            ['code', 'match', 'pattern' => '/^[A-Z0-9]{2,10}$/D', 'message' => '代码应为 2 至 10 位英文字母或数字。'],
            ['code', 'unique'],
            ['continent', 'in', 'range' => self::CONTINENTS],
            [['flag', 'logo'], 'string', 'max' => 255],
            [['flag', 'logo'], AssetPathValidator::class],
            ['intro', 'string', 'max' => 15000],
        ];
    }

    public function attributeLabels() {
        return ['id' => '编号', 'name' => '球队名称', 'code' => 'FIFA 代码', 'continent' => '所属大洲',
            'flag' => '国旗路径', 'logo' => '队徽路径', 'intro' => '球队简介', 'create_time' => '创建时间'];
    }

    public static function options() {
        return self::find()->select('name')->orderBy(['name' => SORT_ASC])->indexBy('id')->column();
    }

    public function rosterQuery($tournamentId = null) {
        $ids = (new Query())->select('player_id')->from('{{%squad}}')->where(['team_id' => $this->id]);
        $ids->andFilterWhere(['tournament_id' => $tournamentId]);
        return Player::find()->where(['id' => $ids]);
    }

    /**
     * 历届战绩:每届的最终阶段与已结束比赛的场次/胜平负/进失球。
     * 只统计 status=2 的比赛;没有已结束比赛的届次也保留一行,标「待补」。
     *
     * @return array
     */
    public function tournamentRecords()
    {
        $stageRank = [
            '小组赛' => 1,
            '1/16决赛' => 2,
            '1/8决赛' => 3,
            '1/4决赛' => 4,
            '半决赛' => 5,
            '三四名决赛' => 6,
            '决赛' => 7,
        ];
        $stageLabel = [
            '小组赛' => '小组赛',
            '1/16决赛' => '32 强',
            '1/8决赛' => '16 强',
            '1/4决赛' => '8 强',
            '半决赛' => '半决赛',
            '三四名决赛' => '三四名决赛',
            '决赛' => '决赛',
        ];

        $rows = (new Query())
            ->select([
                'tournament_id' => 't.id',
                'year' => 't.year',
                'stage' => 'g.stage',
                'home_team_id' => 'g.home_team_id',
                'away_team_id' => 'g.away_team_id',
                'home_score' => 'g.home_score',
                'away_score' => 'g.away_score',
            ])
            ->from(['g' => '{{%game}}'])
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = g.tournament_id')
            ->where(['or', ['g.home_team_id' => $this->id], ['g.away_team_id' => $this->id]])
            ->andWhere(['g.status' => 2])
            ->orderBy(['t.year' => SORT_DESC])
            ->all();

        $records = [];
        foreach ($rows as $row) {
            $tournamentId = (int) $row['tournament_id'];
            if (!isset($records[$tournamentId])) {
                $records[$tournamentId] = [
                    'tournament_id' => $tournamentId,
                    'year' => (int) $row['year'],
                    'stage' => '小组赛',
                    'stage_rank' => 0,
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                ];
            }

            $isHome = (int) $row['home_team_id'] === (int) $this->id;
            $goalsFor = (int) ($isHome ? $row['home_score'] : $row['away_score']);
            $goalsAgainst = (int) ($isHome ? $row['away_score'] : $row['home_score']);

            $records[$tournamentId]['played']++;
            $records[$tournamentId]['goals_for'] += $goalsFor;
            $records[$tournamentId]['goals_against'] += $goalsAgainst;
            if ($goalsFor > $goalsAgainst) {
                $records[$tournamentId]['won']++;
            } elseif ($goalsFor === $goalsAgainst) {
                $records[$tournamentId]['drawn']++;
            } else {
                $records[$tournamentId]['lost']++;
            }

            $rank = $stageRank[$row['stage']] ?? 0;
            if ($rank > $records[$tournamentId]['stage_rank']) {
                $records[$tournamentId]['stage_rank'] = $rank;
                $records[$tournamentId]['stage'] = $stageLabel[$row['stage']] ?? $row['stage'];
            }
        }

        // 参赛但尚未录入已结束比赛的届次,仍列出来并标「待补」。
        foreach ($this->tournaments() as $tournament) {
            $tournamentId = (int) $tournament['id'];
            if (!isset($records[$tournamentId])) {
                $records[$tournamentId] = [
                    'tournament_id' => $tournamentId,
                    'year' => (int) $tournament['year'],
                    'stage' => '待补',
                    'stage_rank' => -1,
                    'played' => 0,
                    'won' => 0,
                    'drawn' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                ];
            }
        }

        usort($records, static function ($a, $b) {
            return $b['year'] <=> $a['year'];
        });

        return $records;
    }

    public function tournaments() {
        return (new Query())->select(['t.id', 't.year', 't.name'])->distinct()
            ->from(['t' => '{{%tournament}}'])->innerJoin(['s' => '{{%squad}}'], 's.tournament_id = t.id')
            ->where(['s.team_id' => $this->id])->orderBy(['t.year' => SORT_DESC])->all();
    }

    public function beforeDelete() {
        if (!parent::beforeDelete()) {
            return false;
        }
        // 留言为多态关系，没有数据库外键，必须额外保护。
        if ((new Query())->from('{{%comment}}')->where(['target_type' => 'team', 'target_id' => $this->id])->exists()) {
            $this->addError('id', '球队已有留言，请先处理留言再删除。');
            return false;
        }
        return true;
    }
}
