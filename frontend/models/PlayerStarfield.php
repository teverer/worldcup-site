<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:届次与比赛 —— 四十年球员星云数据
 *
 * 给统计页的「球员星云」提供一张跨届球员表：生涯进球/助攻、参赛届数、
 * 代表球队/大洲/位置，以及逐届进球与助攻（前端按年份累加播放）。
 * 口径与站内一致：不含点球大战；乌龙球不计入射手，也不计助攻。
 */

namespace frontend\models;

use common\components\TeamFlag;
use Yii;
use yii\db\Query;

class PlayerStarfield
{
    /**
     * 缓存键：数据只随库更新，6 小时足够；演示前如需刷新可清 runtime/cache。
     */
    private const CACHE_KEY = 'player-starfield-v4';
    private const CACHE_TTL = 21600;

    /**
     * @return array{years:int[], counts:array, players:array}
     */
    public static function data(): array
    {
        $cached = Yii::$app->cache->get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $years = (new Query())
            ->select(['year'])
            ->from('tournament')
            ->orderBy(['year' => SORT_ASC])
            ->column();
        $years = array_map('intval', $years);
        $yearIndex = array_flip($years);
        $yearCount = count($years);

        // 所有已结束比赛的进球（点球大战单独排除；乌龙球保留在行里，
        // 但不能算到射手头上，也不能算助攻）。
        $goalRows = (new Query())
            ->select([
                'g.player_id',
                'g.assist_player_id',
                'g.team_id',
                'g.is_own_goal',
                't.year',
            ])
            ->from(['g' => 'goal'])
            ->innerJoin(['gm' => 'game'], 'gm.id = g.game_id')
            ->innerJoin(['t' => 'tournament'], 't.id = gm.tournament_id')
            ->where(['g.is_shootout' => 0])
            ->all();

        $goals = [];
        $assists = [];
        $teamVotes = [];
        $goalYears = [];

        foreach ($goalRows as $row) {
            $year = (int) $row['year'];
            $index = $yearIndex[$year] ?? null;
            if ($index === null) {
                continue;
            }
            $isOwnGoal = (int) $row['is_own_goal'] === 1;
            if ($isOwnGoal) {
                continue;
            }

            $playerId = (int) $row['player_id'];
            $teamId = (int) $row['team_id'];
            if ($playerId > 0) {
                $goals[$playerId][$index] = ($goals[$playerId][$index] ?? 0) + 1;
                $teamVotes[$playerId][$teamId] = ($teamVotes[$playerId][$teamId] ?? 0) + 1;
                $goalYears[$playerId][$year] = true;
            }

            $assistId = (int) ($row['assist_player_id'] ?? 0);
            if ($assistId > 0 && $assistId !== $playerId) {
                $assists[$assistId][$index] = ($assists[$assistId][$index] ?? 0) + 1;
                $teamVotes[$assistId][$teamId] = ($teamVotes[$assistId][$teamId] ?? 0) + 1;
                $goalYears[$assistId][$year] = true;
            }
        }

        $playerIds = array_values(array_unique(array_merge(
            array_keys($goals),
            array_keys($assists)
        )));
        if ($playerIds === []) {
            return ['years' => $years, 'counts' => [
                'players' => 0, 'scorers' => 0, 'assisters' => 0,
            ], 'players' => []];
        }

        $players = (new Query())
            ->select(['id', 'name', 'name_zh', 'position'])
            ->from('player')
            ->where(['id' => $playerIds])
            ->indexBy('id')
            ->all();

        // 参赛届数与代表球队：以 squad 为准；数据缺口时回退到进球年份。
        $squadRows = (new Query())
            ->select(['s.player_id', 's.team_id', 's.position', 't.year'])
            ->from(['s' => 'squad'])
            ->innerJoin(['t' => 'tournament'], 't.id = s.tournament_id')
            ->where(['s.player_id' => $playerIds])
            ->all();

        $squadYears = [];
        $squadPosition = [];
        foreach ($squadRows as $row) {
            $pid = (int) $row['player_id'];
            $year = (int) $row['year'];
            $squadYears[$pid][$year] = true;
            $teamId = (int) $row['team_id'];
            if ($year === max($squadYears[$pid] ? array_keys($squadYears[$pid]) : [$year])) {
                $squadPosition[$pid] = (string) ($row['position'] ?? '');
                if (!isset($teamVotes[$pid][$teamId])) {
                    $teamVotes[$pid][$teamId] = 0;
                }
            }
        }

        $teamIds = [];
        foreach ($teamVotes as $votes) {
            foreach (array_keys($votes) as $teamId) {
                $teamIds[(int) $teamId] = true;
            }
        }
        $teams = (new Query())
            ->select(['id', 'name', 'code', 'continent'])
            ->from('team')
            ->where(['id' => array_keys($teamIds)])
            ->indexBy('id')
            ->all();

        $out = [];
        foreach ($playerIds as $pid) {
            $p = $players[$pid] ?? null;
            if ($p === null) {
                continue;
            }

            $g = array_fill(0, $yearCount, 0);
            $a = array_fill(0, $yearCount, 0);
            $s = array_fill(0, $yearCount, 0);
            foreach ($goals[$pid] ?? [] as $i => $v) {
                $g[$i] = (int) $v;
            }
            foreach ($assists[$pid] ?? [] as $i => $v) {
                $a[$i] = (int) $v;
            }
            $playedYearMap = array_merge(
                array_keys($squadYears[$pid] ?? []),
                array_keys($goalYears[$pid] ?? [])
            );
            foreach (array_unique($playedYearMap) as $year) {
                $yi = $yearIndex[(int) $year] ?? null;
                if ($yi !== null) {
                    $s[$yi] = 1;
                }
            }

            $votes = $teamVotes[$pid] ?? [];
            arsort($votes);
            $teamId = $votes !== [] ? (int) array_key_first($votes) : 0;
            $team = $teams[$teamId] ?? null;

            $playedYears = array_unique(array_merge(
                array_keys($squadYears[$pid] ?? []),
                array_keys($goalYears[$pid] ?? [])
            ));
            $playedYears = array_map('intval', $playedYears);
            sort($playedYears);

            $out[] = [
                'id' => (int) $pid,
                'name' => (string) $p['name'],
                'name_zh' => (string) ($p['name_zh'] ?? ''),
                'position' => (string) ($squadPosition[$pid] ?? ($p['position'] ?? '')),
                'team' => (string) ($team['name'] ?? ''),
                'code' => (string) ($team['code'] ?? ''),
                'flag' => TeamFlag::url($team['code'] ?? null),
                'continent' => (string) ($team['continent'] ?? ''),
                'first' => $playedYears !== [] ? $playedYears[0] : 0,
                'last' => $playedYears !== [] ? $playedYears[count($playedYears) - 1] : 0,
                'editions' => count($playedYears),
                'goals' => array_sum($g),
                'assists' => array_sum($a),
                'g' => $g,
                'a' => $a,
                's' => $s,
            ];
        }

        usort($out, static function ($x, $y) {
            return [$y['goals'], $y['assists'], $x['id']] <=> [$x['goals'], $x['assists'], $y['id']];
        });

        $scorers = 0;
        $assisters = 0;
        foreach ($out as $p) {
            if ($p['goals'] > 0) {
                $scorers++;
            }
            if ($p['assists'] > 0) {
                $assisters++;
            }
        }

        $data = [
            'years' => $years,
            'counts' => [
                'players' => (int) (new Query())->from('player')->count(),
                'scorers' => $scorers,
                'assisters' => $assisters,
            ],
            'players' => $out,
        ];

        Yii::$app->cache->set(self::CACHE_KEY, $data, self::CACHE_TTL);

        return $data;
    }
}
