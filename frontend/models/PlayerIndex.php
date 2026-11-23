<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 球员索引页的「每届最佳射手」入口。
 *
 * 只做一件事:按届次取进球最多的一位球员,给「不记得名字」的访客一个可读的起点。
 * 口径与射手榜一致:已结束比赛,排除乌龙球与点球大战。
 */

namespace frontend\models;

use yii\db\Query;

class PlayerIndex
{
    /**
     * 每届进球最多的球员,新届在前;并列时记下并列人数。
     *
     * @return array<int,array{year:int,player_id:int,name:string,name_zh:?string,nationality:?string,team_name:?string,goals:int,tied:int}>
     */
    public static function topScorersByTournament()
    {
        $rows = (new Query())
            ->select([
                'year' => 't.year',
                'player_id' => 'p.id',
                'name' => 'p.name',
                'name_zh' => 'p.name_zh',
                'nationality' => 'p.nationality',
                'team_name' => 'tm.name',
                'goals' => 'COUNT(*)',
            ])
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['m' => '{{%game}}'], 'm.id = g.game_id')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = m.tournament_id')
            ->innerJoin(['p' => '{{%player}}'], 'p.id = g.player_id')
            ->leftJoin(['s' => '{{%squad}}'], 's.player_id = p.id AND s.tournament_id = t.id')
            ->leftJoin(['tm' => '{{%team}}'], 'tm.id = s.team_id')
            ->where(['m.status' => 2, 'g.is_own_goal' => 0, 'g.is_shootout' => 0])
            ->groupBy(['t.id', 't.year', 'p.id', 'p.name', 'p.name_zh', 'p.nationality', 'tm.name'])
            ->orderBy(['t.year' => SORT_DESC, 'goals' => SORT_DESC, 'p.name' => SORT_ASC])
            ->all();

        $leaders = [];
        foreach ($rows as $row) {
            $year = (int) $row['year'];
            if (!isset($leaders[$year])) {
                $row['year'] = $year;
                $row['player_id'] = (int) $row['player_id'];
                $row['goals'] = (int) $row['goals'];
                $row['tied'] = 1;
                $leaders[$year] = $row;
                continue;
            }
            if ((int) $row['goals'] === $leaders[$year]['goals']) {
                $leaders[$year]['tied']++;
            }
        }

        return array_values($leaders);
    }
}
