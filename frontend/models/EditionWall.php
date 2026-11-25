<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261122
 * 模块:界面设计 —— 根页记分牌墙与届次编年数据
 *
 * 一次载入全部届次,再用聚合查询补齐场次、进球、决赛与冠军,
 * 供根页记分牌墙和届次列表页共用,避免在视图里逐届查询造成 N+1。
 */

namespace frontend\models;

use common\models\Game;
use common\models\Tournament;
use yii\db\Expression;
use yii\db\Query;

/**
 * 届次编年数据构建器。
 */
class EditionWall
{
    /**
     * 返回按年份倒序排列的届次数据。
     *
     * @return array{editions: array, decades: array, stats: array}
     */
    public static function all()
    {
        $tournaments = Tournament::find()
            ->orderBy(['year' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        $tournamentIds = array_map(static function ($tournament) {
            return (int) $tournament->id;
        }, $tournaments);

        $aggregates = [];
        $finals = [];
        $finalGoalMinutes = [];

        if ($tournamentIds !== []) {
            // 1) 每届已结束比赛的场次与总进球(1 条聚合查询)。
            $aggregateRows = (new Query())
                ->select([
                    'tournament_id',
                    'game_count' => new Expression('COUNT(*)'),
                    'goal_count' => new Expression('COALESCE(SUM(home_score + away_score), 0)'),
                ])
                ->from('{{%game}}')
                ->where([
                    'tournament_id' => $tournamentIds,
                    'status' => Game::STATUS_FINISHED,
                ])
                ->groupBy('tournament_id')
                ->all();

            foreach ($aggregateRows as $row) {
                $aggregates[(int) $row['tournament_id']] = [
                    'games' => (int) $row['game_count'],
                    'goals' => (int) $row['goal_count'],
                ];
            }

            // 2) 每届决赛(含主客队名称,1 条查询)。
            $finals = Game::find()
                ->with(['homeTeam', 'awayTeam'])
                ->where([
                    'tournament_id' => $tournamentIds,
                    'stage' => Game::STAGE_FINAL,
                    'status' => Game::STATUS_FINISHED,
                ])
                ->orderBy(['id' => SORT_DESC])
                ->indexBy('tournament_id')
                ->all();

            $finalIds = array_map(static function ($final) {
                return (int) $final->id;
            }, $finals);

            if ($finalIds !== []) {
                // 3) 决赛最大进球分钟,用于判断是否加时(1 条聚合查询)。
                $goalRows = (new Query())
                    ->select(['game_id', 'max_minute' => new Expression('MAX(minute)')])
                    ->from('{{%goal}}')
                    ->where(['game_id' => $finalIds])
                    ->groupBy('game_id')
                    ->all();

                foreach ($goalRows as $row) {
                    $finalGoalMinutes[(int) $row['game_id']] = $row['max_minute'] !== null
                        ? (int) $row['max_minute']
                        : null;
                }
            }
        }

        $editions = [];
        $decades = [];
        $totalTeams = 0;
        $totalGames = 0;

        foreach ($tournaments as $tournament) {
            $tournamentId = (int) $tournament->id;
            $aggregate = isset($aggregates[$tournamentId])
                ? $aggregates[$tournamentId]
                : ['games' => 0, 'goals' => 0];
            $final = isset($finals[$tournamentId]) ? $finals[$tournamentId] : null;
            $maxMinute = $final !== null && isset($finalGoalMinutes[(int) $final->id])
                ? $finalGoalMinutes[(int) $final->id]
                : null;

            $edition = [
                'id' => $tournamentId,
                'year' => (int) $tournament->year,
                'ordinal' => self::tournamentOrdinal((int) $tournament->year),
                'name' => (string) $tournament->name,
                'host' => $tournament->host_country !== null && $tournament->host_country !== ''
                    ? (string) $tournament->host_country
                    : '待定',
                'date_range' => self::dateRange($tournament->start_date, $tournament->end_date),
                'team_count' => $tournament->team_count !== null ? (int) $tournament->team_count : null,
                'format' => $tournament->format !== null && $tournament->format !== ''
                    ? (string) $tournament->format
                    : '',
                'games' => $aggregate['games'],
                'goals' => $aggregate['goals'],
                'final' => $final !== null ? self::finalSummary($final, $maxMinute) : null,
            ];

            $editions[] = $edition;
            $decades[(int) (floor($edition['year'] / 10) * 10)][] = $edition;
            $totalTeams += (int) $tournament->team_count;
            $totalGames += $aggregate['games'];
        }

        return [
            'editions' => $editions,
            'decades' => $decades,
            'stats' => [
                ['value' => count($tournaments), 'label' => '届次'],
                ['value' => $totalTeams, 'label' => '参赛队累计'],
                ['value' => $totalGames, 'label' => '比赛总场次'],
            ],
        ];
    }

    /**
     * FIFA 官方届次数:1986 年为第 13 届,此后每 4 年递增一届。
     *
     * @param int $year
     * @return int
     */
    private static function tournamentOrdinal($year)
    {
        return 13 + (int) round(($year - 1986) / 4);
    }

    /**
     * 起止日期格式化为“2026.06.11 — 2026.07.19”。
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return string
     */
    private static function dateRange($startDate, $endDate)
    {
        $format = static function ($date) {
            return str_replace('-', '.', (string) $date);
        };

        if ($startDate !== null && $startDate !== '' && $endDate !== null && $endDate !== '') {
            return $format($startDate) . ' — ' . $format($endDate);
        }
        if ($startDate !== null && $startDate !== '') {
            return $format($startDate) . ' 开幕';
        }
        if ($endDate !== null && $endDate !== '') {
            return $format($endDate) . ' 闭幕';
        }

        return '赛期待定';
    }

    /**
     * 从已结束的决赛推导冠军。常规比分相等时比较点球大战比分。
     *
     * @param Game $final
     * @param int|null $maxMinute
     * @return array
     */
    private static function finalSummary(Game $final, $maxMinute)
    {
        $homeName = $final->homeTeam !== null ? (string) $final->homeTeam->name : '待定';
        $awayName = $final->awayTeam !== null ? (string) $final->awayTeam->name : '待定';
        $homeScore = (int) $final->home_score;
        $awayScore = (int) $final->away_score;

        $hasPenalty = $final->home_penalty_score !== null && $final->away_penalty_score !== null;
        $homePenalty = $hasPenalty ? (int) $final->home_penalty_score : null;
        $awayPenalty = $hasPenalty ? (int) $final->away_penalty_score : null;

        if ($homeScore > $awayScore) {
            $champion = $homeName;
            $runnerUp = $awayName;
        } elseif ($awayScore > $homeScore) {
            $champion = $awayName;
            $runnerUp = $homeName;
        } elseif ($hasPenalty && $homePenalty > $awayPenalty) {
            $champion = $homeName;
            $runnerUp = $awayName;
        } elseif ($hasPenalty && $awayPenalty > $homePenalty) {
            $champion = $awayName;
            $runnerUp = $homeName;
        } else {
            // 比分与点球都无法分出胜负,视为数据待补,不臆造冠军。
            $champion = null;
            $runnerUp = null;
        }

        // 点球大战必然发生在加时之后;没有点球时用 90 分钟后的进球判断加时。
        $extraTime = $hasPenalty || ($maxMinute !== null && $maxMinute > 90);

        // 决赛比分统一按“冠军在前、对手在后”排列,避免客队夺冠时读反。
        $championIsHome = $champion !== null && $champion === $homeName;
        $championScore = $champion === null ? null : ($championIsHome ? $homeScore : $awayScore);
        $opponentScore = $champion === null ? null : ($championIsHome ? $awayScore : $homeScore);
        $championPenalty = $hasPenalty && $champion !== null
            ? ($championIsHome ? $homePenalty : $awayPenalty)
            : null;
        $opponentPenalty = $hasPenalty && $champion !== null
            ? ($championIsHome ? $awayPenalty : $homePenalty)
            : null;

        $championTeam = $championIsHome ? $final->homeTeam : $final->awayTeam;
        $opponentTeam = $championIsHome ? $final->awayTeam : $final->homeTeam;

        return [
            'home' => $homeName,
            'away' => $awayName,
            'champion' => $champion,
            'runner_up' => $runnerUp,
            'opponent' => $runnerUp,
            'champion_code' => $champion !== null && $championTeam !== null ? (string) $championTeam->code : null,
            'opponent_code' => $champion !== null && $opponentTeam !== null ? (string) $opponentTeam->code : null,
            'champion_score' => $championScore,
            'opponent_score' => $opponentScore,
            'has_penalty' => $hasPenalty,
            'penalty_text' => $hasPenalty ? $championPenalty . '-' . $opponentPenalty : '',
            'extra_time' => $extraTime,
        ];
    }
}
