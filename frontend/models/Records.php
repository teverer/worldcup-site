<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:纪录页 —— 跨十一届的纪录聚合（冠军、射手、比分、单届产量）
 *
 * 纪录页只回答一件事：四十年里，哪些数字被留下来了。
 * 所有数字都从 game / goal / tournament 聚合，不写死；某一届数据补齐后自动更新。
 * 口径与站点其他统计一致：只算已结束比赛；射手排除乌龙球与点球大战进球。
 */

namespace frontend\models;

use common\models\Game;
use yii\db\Expression;
use yii\db\Query;

class Records
{
    /**
     * 统计页「历史坐标」需要的三块：四强、决赛常客、生涯射手。
     * 不计算进球时钟与四十年之最，避免把已删除的内容继续查一遍。
     *
     * @return array
     */
    public static function history()
    {
        $finals = self::finals();

        return [
            'totals' => self::totals(),
            'four' => self::finalFour(),
            'nations' => self::finalNations($finals),
            'scorers' => self::careerScorers(11),
        ];
    }

    /**
     * 某一届的球员进球数，排除乌龙球与点球大战，只算已结束比赛。
     *
     * @param int|string $tournamentId
     * @return array<int,int> player_id => goals
     */
    public static function currentEditionScorers($tournamentId)
    {
        $rows = (new Query())
            ->select(['player_id' => 'g.player_id', 'goals' => new Expression('COUNT(*)')])
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['ga' => '{{%game}}'], 'ga.id = g.game_id')
            ->where([
                'ga.tournament_id' => (int) $tournamentId,
                'ga.status' => Game::STATUS_FINISHED,
                'g.is_own_goal' => 0,
                'g.is_shootout' => 0,
            ])
            ->groupBy('g.player_id')
            ->all();

        $goals = [];
        foreach ($rows as $row) {
            $goals[(int) $row['player_id']] = (int) $row['goals'];
        }

        return $goals;
    }

    /**
     * 一次取回纪录页需要的全部数据。
     *
     * @return array
     */
    public static function all()
    {
        $finals = self::finals();
        $scorers = self::careerScorers(11);

        return [
            'totals' => self::totals(),
            'moments' => self::moments($finals, $scorers),
            'clock' => self::goalClock(),
            'four' => self::finalFour(),
            'finals' => $finals,
            'nations' => self::finalNations($finals),
            'scorers' => $scorers,
        ];
    }

    /**
     * 十一届的总场次、总进球与届数。
     *
     * @return array
     */
    private static function totals()
    {
        $totals = (new Query())
            ->select([
                'games' => new Expression('COUNT(*)'),
                'goals' => new Expression('COALESCE(SUM(home_score + away_score), 0)'),
            ])
            ->from('{{%game}}')
            ->where(['status' => Game::STATUS_FINISHED])
            ->one();

        return [
            'games' => (int) $totals['games'],
            'goals' => (int) $totals['goals'],
            'editions' => (int) (new Query())->from('{{%tournament}}')->count(),
        ];
    }

    /**
     * 十一场决赛，含冠军、比分与点球结果。
     *
     * @return array
     */
    private static function finals()
    {
        $rows = (new Query())
            ->select([
                'game_id' => 'g.id',
                'tournament_id' => 't.id',
                'year' => 't.year',
                'host' => 't.host_country',
                'home_score' => 'g.home_score',
                'away_score' => 'g.away_score',
                'home_penalty' => 'g.home_penalty_score',
                'away_penalty' => 'g.away_penalty_score',
                'home_id' => 'ht.id',
                'home_name' => 'ht.name',
                'home_code' => 'ht.code',
                'away_id' => 'at.id',
                'away_name' => 'at.name',
                'away_code' => 'at.code',
            ])
            ->from(['g' => '{{%game}}'])
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = g.tournament_id')
            ->innerJoin(['ht' => '{{%team}}'], 'ht.id = g.home_team_id')
            ->innerJoin(['at' => '{{%team}}'], 'at.id = g.away_team_id')
            ->where(['g.stage' => Game::STAGE_FINAL, 'g.status' => Game::STATUS_FINISHED])
            ->orderBy(['t.year' => SORT_DESC, 'g.id' => SORT_DESC])
            ->all();

        $result = [];
        foreach ($rows as $row) {
            $homeScore = (int) $row['home_score'];
            $awayScore = (int) $row['away_score'];
            $hasPenalty = $row['home_penalty'] !== null && $row['away_penalty'] !== null;
            $homePenalty = $hasPenalty ? (int) $row['home_penalty'] : null;
            $awayPenalty = $hasPenalty ? (int) $row['away_penalty'] : null;

            $homeWon = $homeScore > $awayScore
                || ($hasPenalty && $homeScore === $awayScore && $homePenalty > $awayPenalty);
            $decided = $homeScore !== $awayScore || ($hasPenalty && $homePenalty !== $awayPenalty);

            $result[] = [
                'game_id' => (int) $row['game_id'],
                'tournament_id' => (int) $row['tournament_id'],
                'year' => (int) $row['year'],
                'host' => (string) $row['host'],
                'champion_id' => $decided ? ($homeWon ? (int) $row['home_id'] : (int) $row['away_id']) : null,
                'champion_name' => $decided ? ($homeWon ? $row['home_name'] : $row['away_name']) : null,
                'champion_code' => $decided ? ($homeWon ? $row['home_code'] : $row['away_code']) : null,
                'runner_id' => $decided ? ($homeWon ? (int) $row['away_id'] : (int) $row['home_id']) : null,
                'runner_name' => $decided ? ($homeWon ? $row['away_name'] : $row['home_name']) : null,
                'runner_code' => $decided ? ($homeWon ? $row['away_code'] : $row['home_code']) : null,
                // 比分按「冠军–亚军」排列，与左侧捧杯球队同序，避免读者把
                // 主客队顺序误读成胜负方向。
                'score' => $homeWon
                    ? $homeScore . '–' . $awayScore
                    : $awayScore . '–' . $homeScore,
                'penalty' => $hasPenalty ? $homePenalty . '–' . $awayPenalty : null,
                'penalty_winner' => $hasPenalty
                    ? ($homePenalty > $awayPenalty ? $row['home_name'] : $row['away_name'])
                    : null,
            ];
        }

        return $result;
    }

    /**
     * 「十一届的进球时钟」：每届进球落在 7 个时段的比例。
     *
     * 这是一个跨届热力图的数据层：每行按该届自身总进球算比例，
     * 不跨届相加，因此可以比较不同比赛数量的届次。点球大战不计。
     *
     * @return array
     */
    private static function goalClock()
    {
        $buckets = [
            ['label' => '1–15', 'lo' => 0, 'hi' => 15],
            ['label' => '16–30', 'lo' => 16, 'hi' => 30],
            ['label' => '31–45+', 'lo' => 31, 'hi' => 45],
            ['label' => '46–60', 'lo' => 46, 'hi' => 60],
            ['label' => '61–75', 'lo' => 61, 'hi' => 75],
            ['label' => '76–90+', 'lo' => 76, 'hi' => 90],
            ['label' => '91–120', 'lo' => 91, 'hi' => 200],
        ];

        $select = ['tournament_id' => 't.id', 'year' => 't.year', 'total' => new Expression('COUNT(*)')];
        foreach ($buckets as $index => $bucket) {
            $select['b' . $index] = new Expression(
                'SUM(CASE WHEN g.minute BETWEEN ' . (int) $bucket['lo'] . ' AND ' . (int) $bucket['hi']
                . ' THEN 1 ELSE 0 END)'
            );
        }

        $rows = (new Query())
            ->select($select)
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['ga' => '{{%game}}'], 'ga.id = g.game_id')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = ga.tournament_id')
            ->where(['ga.status' => Game::STATUS_FINISHED, 'g.is_shootout' => 0])
            ->andWhere(['not', ['g.minute' => null]])
            ->groupBy('t.id')
            ->orderBy(['t.year' => SORT_DESC])
            ->all();

        $editions = [];
        $maxLate = null;
        $minLate = null;
        foreach ($rows as $row) {
            $total = (int) $row['total'];
            if ($total < 1) {
                continue;
            }

            $cells = [];
            $late = 0;
            foreach ($buckets as $index => $bucket) {
                $count = (int) $row['b' . $index];
                $percent = round($count / $total * 100, 1);
                if ((int) $bucket['lo'] >= 46) {
                    $late += $count;
                }
                $cells[] = [
                    'label' => $bucket['label'],
                    'count' => $count,
                    'percent' => $percent,
                    // 连续色阶：比例越高，金色越亮；数值同时写在格子里。
                    'alpha' => round(min(.62, .04 + $percent * .026), 3),
                ];
            }

            $latePercent = round($late / $total * 100, 1);
            $edition = [
                'tournament_id' => (int) $row['tournament_id'],
                'year' => (int) $row['year'],
                'total' => $total,
                'cells' => $cells,
                'late_percent' => $latePercent,
            ];
            $editions[] = $edition;

            if ($maxLate === null || $latePercent > $maxLate['late_percent']) {
                $maxLate = $edition;
            }
            if ($minLate === null || $latePercent < $minLate['late_percent']) {
                $minLate = $edition;
            }
        }

        return [
            'buckets' => array_map(static function ($bucket) {
                return $bucket['label'];
            }, $buckets),
            'editions' => $editions,
            'max_late' => $maxLate,
            'min_late' => $minLate,
        ];
    }

    /**
     * 「四强版图」：每届半决赛四支球队按大洲的构成。
     *
     * 一行一届，四支球队各占四分之一；按洲着色，占比天然是 25% 的倍数。
     * 这里只做跨届聚合，球队名保留在行 title / 当前届上下文里。
     *
     * @return array
     */
    private static function finalFour()
    {
        $rows = (new Query())
            ->select([
                'tournament_id' => 't.id',
                'year' => 't.year',
                'host' => 't.host_country',
                'team_id' => 'tm.id',
                'team_name' => 'tm.name',
                'team_code' => 'tm.code',
                'continent' => 'tm.continent',
            ])
            ->from(['g' => '{{%game}}'])
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = g.tournament_id')
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = g.home_team_id OR tm.id = g.away_team_id')
            ->where(['g.stage' => Game::STAGE_SEMI_FINAL, 'g.status' => Game::STATUS_FINISHED])
            ->orderBy(['t.year' => SORT_DESC, 'tm.id' => SORT_ASC])
            ->all();

        $order = ['欧洲', '南美洲', '非洲', '亚洲', '中北美', '大洋洲', '其他'];
        $editions = [];
        foreach ($rows as $row) {
            $id = (int) $row['tournament_id'];
            if (!isset($editions[$id])) {
                $editions[$id] = [
                    'tournament_id' => $id,
                    'year' => (int) $row['year'],
                    'host' => (string) $row['host'],
                    'teams' => [],
                ];
            }
            $teamId = (int) $row['team_id'];
            if (!isset($editions[$id]['teams'][$teamId])) {
                $editions[$id]['teams'][$teamId] = [
                    'name' => (string) $row['team_name'],
                    'code' => (string) $row['team_code'],
                    'continent' => $row['continent'] !== null && $row['continent'] !== ''
                        ? (string) $row['continent']
                        : '其他',
                ];
            }
        }

        $usedContinents = [];
        foreach ($editions as $id => $edition) {
            $teams = array_values($edition['teams']);
            $counts = [];
            foreach ($teams as $team) {
                $counts[$team['continent']] = isset($counts[$team['continent']])
                    ? $counts[$team['continent']] + 1
                    : 1;
            }

            $segments = [];
            foreach ($order as $continent) {
                if (empty($counts[$continent])) {
                    continue;
                }
                $count = (int) $counts[$continent];
                $segments[] = [
                    'continent' => $continent,
                    'count' => $count,
                    'percent' => $count * 25,
                ];
                $usedContinents[$continent] = true;
            }

            usort($teams, static function ($a, $b) use ($order) {
                $orderIndex = array_flip($order);

                return $orderIndex[$a['continent']] <=> $orderIndex[$b['continent']]
                    ?: strcmp($a['name'], $b['name']);
            });

            $editions[$id]['teams'] = $teams;
            $editions[$id]['segments'] = $segments;
        }

        $legends = [];
        foreach ($order as $continent) {
            if (isset($usedContinents[$continent])) {
                $legends[] = $continent;
            }
        }

        return [
            'legends' => $legends,
            'editions' => array_values($editions),
        ];
    }

    /**
     * 决赛常客：按国家合并西德 / 德国后，统计冠军、亚军与决赛次数。
     *
     * 这是对上面十一场决赛的聚合，不引入新数据；合并只发生在「同一国家
     * 的两个历史队名」上，不合并其他球队。
     *
     * @param array $finals
     * @return array
     */
    private static function finalNations(array $finals)
    {
        $nations = [];

        $add = static function ($id, $name, $code, $year, $isChampion) use (&$nations) {
            $code = (string) $code;
            $key = in_array($code, ['FRG', 'GER'], true) ? 'GER' : ($code !== '' ? $code : (string) $id);
            $isGermany = $key === 'GER';

            if (!isset($nations[$key])) {
                $nations[$key] = [
                    'id' => (int) $id,
                    'name' => $isGermany ? '德国' : (string) $name,
                    'code' => $isGermany ? 'GER' : $code,
                    'titles' => 0,
                    'runner_up' => 0,
                    'finals' => 0,
                    'first' => (int) $year,
                    'last' => (int) $year,
                ];
            }

            $nation = &$nations[$key];
            $nation['finals']++;
            if ($isChampion) {
                $nation['titles']++;
            } else {
                $nation['runner_up']++;
            }
            $nation['first'] = min($nation['first'], (int) $year);
            if ((int) $year >= $nation['last']) {
                $nation['last'] = (int) $year;
                $nation['id'] = (int) $id;
                if (!$isGermany) {
                    $nation['name'] = (string) $name;
                }
            }
            unset($nation);
        };

        foreach ($finals as $final) {
            if ($final['champion_code'] !== null) {
                $add($final['champion_id'], $final['champion_name'], $final['champion_code'], $final['year'], true);
            }
            if ($final['runner_code'] !== null) {
                $add($final['runner_id'], $final['runner_name'], $final['runner_code'], $final['year'], false);
            }
        }

        $nations = array_values($nations);
        usort($nations, static function ($a, $b) {
            if ($a['titles'] !== $b['titles']) {
                return $b['titles'] <=> $a['titles'];
            }
            if ($a['runner_up'] !== $b['runner_up']) {
                return $b['runner_up'] <=> $a['runner_up'];
            }
            if ($a['finals'] !== $b['finals']) {
                return $b['finals'] <=> $a['finals'];
            }

            return $a['first'] <=> $b['first'];
        });

        return $nations;
    }

    /**
     * 跨届生涯进球榜（不含乌龙球与点球大战）。
     *
     * @param int $limit
     * @return array
     */
    private static function careerScorers($limit)
    {
        $rows = (new Query())
            ->select([
                'player_id' => 'p.id',
                'name' => 'p.name',
                'name_zh' => 'p.name_zh',
                'nationality' => 'p.nationality',
                'goals' => new Expression('COUNT(*)'),
                'first_year' => new Expression('MIN(t.year)'),
                'last_year' => new Expression('MAX(t.year)'),
                'editions' => new Expression('COUNT(DISTINCT t.id)'),
            ])
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['ga' => '{{%game}}'], 'ga.id = g.game_id')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = ga.tournament_id')
            ->innerJoin(['p' => '{{%player}}'], 'p.id = g.player_id')
            ->where(['g.is_own_goal' => 0, 'g.is_shootout' => 0])
            ->groupBy('p.id')
            ->orderBy(['goals' => SORT_DESC, 'last_year' => SORT_DESC, 'p.id' => SORT_ASC])
            ->limit($limit)
            ->all();

        $nationalities = array_values(array_unique(array_filter(array_map(static function ($row) {
            return (string) $row['nationality'];
        }, $rows))));
        $codes = [];
        if ($nationalities !== []) {
            $teamRows = (new Query())
                ->select(['name', 'code'])
                ->from('{{%team}}')
                ->where(['name' => $nationalities])
                ->all();
            foreach ($teamRows as $team) {
                $codes[(string) $team['name']] = (string) $team['code'];
            }
        }

        $result = [];
        foreach ($rows as $index => $row) {
            $result[] = [
                'rank' => $index + 1,
                'player_id' => (int) $row['player_id'],
                'display_name' => $row['name_zh'] !== null && $row['name_zh'] !== ''
                    ? (string) $row['name_zh']
                    : (string) $row['name'],
                'name' => (string) $row['name'],
                'nationality' => (string) $row['nationality'],
                'code' => isset($codes[(string) $row['nationality']]) ? $codes[(string) $row['nationality']] : '',
                'goals' => (int) $row['goals'],
                'first_year' => (int) $row['first_year'],
                'last_year' => (int) $row['last_year'],
                'editions' => (int) $row['editions'],
            ];
        }

        return $result;
    }

    /**
     * 六条「四十年之最」。每条都有数字、事实和去向，不写形容词堆砌。
     *
     * @param array $finals
     * @param array $scorers
     * @return array
     */
    private static function moments(array $finals, array $scorers)
    {
        $moments = [];

        if ($scorers !== []) {
            $top = $scorers[0];
            $moments[] = [
                'value' => (string) $top['goals'],
                'unit' => '球',
                'title' => '生涯总进球',
                'copy' => $top['display_name'] . '在 ' . $top['first_year'] . '–' . $top['last_year']
                    . ' 的三届世界杯打进 ' . $top['goals'] . ' 球，是十一届里的第一人。',
                'meta' => $top['nationality'] . ' · ' . $top['editions'] . ' 届',
                'url' => ['/player/view', 'id' => $top['player_id']],
                'action' => '看他的进球',
            ];
        }

        $highest = self::highestScoringGame();
        if ($highest !== null) {
            $moments[] = [
                'value' => (string) $highest['total'],
                'unit' => '球',
                'title' => '单场总进球',
                'copy' => $highest['year'] . ' 年 ' . $highest['home_name'] . ' ' . $highest['home_score']
                    . '–' . $highest['away_score'] . ' ' . $highest['away_name']
                    . '，一场进了 ' . $highest['total'] . ' 个。',
                'meta' => $highest['stage'],
                'url' => ['/game/view', 'id' => $highest['game_id']],
                'action' => '回到这一场',
            ];
        }

        $biggest = self::biggestWin();
        if ($biggest !== null) {
            $moments[] = [
                'value' => (string) $biggest['margin'],
                'unit' => '球差',
                'title' => '最大分差',
                'copy' => $biggest['year'] . ' 年 ' . $biggest['home_name'] . ' ' . $biggest['home_score']
                    . '–' . $biggest['away_score'] . ' ' . $biggest['away_name'] . '。',
                'meta' => $biggest['stage'],
                'url' => ['/game/view', 'id' => $biggest['game_id']],
                'action' => '看这一场',
            ];
        }

        $peak = self::highestScoringEdition();
        if ($peak !== null) {
            $moments[] = [
                'value' => (string) $peak['goals'],
                'unit' => '球',
                'title' => '单届总进球',
                'copy' => $peak['year'] . ' 年 ' . $peak['games'] . ' 场打进 ' . $peak['goals']
                    . ' 球，场均 ' . number_format($peak['average'], 2) . '，是十一届最高。',
                'meta' => $peak['host'],
                'url' => ['/tournament/view', 'id' => $peak['tournament_id']],
                'action' => '进入这一届',
            ];
        }

        $penaltyFinals = array_values(array_filter($finals, static function ($final) {
            return $final['penalty'] !== null;
        }));
        if ($penaltyFinals !== []) {
            $years = array_map(static function ($final) {
                return (string) $final['year'];
            }, array_reverse($penaltyFinals));
            $winner = $penaltyFinals[0];
            $moments[] = [
                'value' => (string) count($penaltyFinals),
                'unit' => '场',
                'title' => '决赛点球大战',
                'copy' => '十一场决赛里，有 ' . count($penaltyFinals) . ' 场拖到点球（'
                    . implode('、', $years) . '）；最近一次是 ' . $winner['year'] . ' 年 '
                    . $winner['champion_name'] . '。',
                'meta' => '决赛 · 点球',
                'url' => ['/game/view', 'id' => $winner['game_id']],
                'action' => '看最近一场',
            ];
        }

        $firstMinute = self::firstMinuteGoals();
        if ($firstMinute !== []) {
            $names = array_map(static function ($goal) {
                $name = $goal['name_zh'] !== null && $goal['name_zh'] !== ''
                    ? $goal['name_zh']
                    : $goal['name'];

                return trim((string) preg_replace('/\s+/u', ' ', $name));
            }, $firstMinute);
            $moments[] = [
                'value' => '1',
                'unit' => '分钟',
                'title' => '开场即进球',
                'copy' => '有 ' . count($firstMinute) . ' 人在第 1 分钟破门：' . implode('、', $names)
                    . '。数据只记到分钟，不分先后。',
                'meta' => implode(' · ', array_map(static function ($goal) {
                    return (string) $goal['year'];
                }, $firstMinute)),
                'url' => ['/game/view', 'id' => $firstMinute[0]['game_id']],
                'action' => '看其中一场',
            ];
        }

        return $moments;
    }

    /**
     * @return array|null
     */
    private static function highestScoringGame()
    {
        return self::gameRow(
            ['total' => new Expression('g.home_score + g.away_score')],
            ['total' => SORT_DESC, 'g.id' => SORT_ASC]
        );
    }

    /**
     * @return array|null
     */
    private static function biggestWin()
    {
        return self::gameRow(
            ['margin' => new Expression('ABS(g.home_score - g.away_score)')],
            ['margin' => SORT_DESC, 'g.id' => SORT_ASC]
        );
    }

    /**
     * @param array $extraSelect
     * @param string $orderExpression
     * @param array $orderBy
     * @return array|null
     */
    private static function gameRow(array $extraSelect, array $orderBy)
    {
        $row = (new Query())
            ->select(array_merge([
                'game_id' => 'g.id',
                'year' => 't.year',
                'home_name' => 'ht.name',
                'away_name' => 'at.name',
                'home_score' => 'g.home_score',
                'away_score' => 'g.away_score',
                'stage' => 'g.stage',
            ], $extraSelect))
            ->from(['g' => '{{%game}}'])
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = g.tournament_id')
            ->innerJoin(['ht' => '{{%team}}'], 'ht.id = g.home_team_id')
            ->innerJoin(['at' => '{{%team}}'], 'at.id = g.away_team_id')
            ->where(['g.status' => Game::STATUS_FINISHED])
            ->andWhere(['not', ['g.home_score' => null]])
            ->andWhere(['not', ['g.away_score' => null]])
            ->orderBy($orderBy)
            ->one();

        if ($row === null) {
            return null;
        }

        $row['game_id'] = (int) $row['game_id'];
        $row['year'] = (int) $row['year'];
        $row['home_score'] = (int) $row['home_score'];
        $row['away_score'] = (int) $row['away_score'];
        if (isset($row['total'])) {
            $row['total'] = (int) $row['total'];
        }
        if (isset($row['margin'])) {
            $row['margin'] = (int) $row['margin'];
        }

        return $row;
    }

    /**
     * 单届进球最多的那一届。
     *
     * @return array|null
     */
    private static function highestScoringEdition()
    {
        $row = (new Query())
            ->select([
                'tournament_id' => 't.id',
                'year' => 't.year',
                'host' => 't.host_country',
                'games' => new Expression('COUNT(*)'),
                'goals' => new Expression('COALESCE(SUM(g.home_score + g.away_score), 0)'),
            ])
            ->from(['g' => '{{%game}}'])
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = g.tournament_id')
            ->where(['g.status' => Game::STATUS_FINISHED])
            ->groupBy('t.id')
            ->orderBy(['goals' => SORT_DESC, 'year' => SORT_ASC])
            ->one();

        if ($row === null || (int) $row['games'] === 0) {
            return null;
        }

        $row['tournament_id'] = (int) $row['tournament_id'];
        $row['year'] = (int) $row['year'];
        $row['games'] = (int) $row['games'];
        $row['goals'] = (int) $row['goals'];
        $row['average'] = $row['goals'] / $row['games'];

        return $row;
    }

    /**
     * 第 1 分钟进球（不含点球大战），按年份升序取前四。
     *
     * @return array
     */
    private static function firstMinuteGoals()
    {
        $rows = (new Query())
            ->select([
                'player_id' => 'p.id',
                'name' => 'p.name',
                'name_zh' => 'p.name_zh',
                'game_id' => 'ga.id',
                'year' => 't.year',
            ])
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['ga' => '{{%game}}'], 'ga.id = g.game_id')
            ->innerJoin(['t' => '{{%tournament}}'], 't.id = ga.tournament_id')
            ->innerJoin(['p' => '{{%player}}'], 'p.id = g.player_id')
            ->where([
                'g.minute' => 1,
                'g.is_shootout' => 0,
                'ga.status' => Game::STATUS_FINISHED,
            ])
            ->orderBy(['t.year' => SORT_ASC, 'ga.id' => SORT_ASC])
            ->limit(4)
            ->all();

        foreach ($rows as $index => $row) {
            $rows[$index]['player_id'] = (int) $row['player_id'];
            $rows[$index]['game_id'] = (int) $row['game_id'];
            $rows[$index]['year'] = (int) $row['year'];
        }

        return $rows;
    }
}
