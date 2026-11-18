<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次详情数据准备
 *
 * 一次性准备届次详情页所需的全部数据:冠军/亚军/季军、决赛、淘汰赛对阵图、
 * 小组积分榜、赛事统计与冠军晋级路径。视图只负责渲染,不再做业务计算。
 *
 * 查询策略:参赛队、比赛、进球、留言统计各用一条批量查询(比赛与球队、
 * 进球与球员/球队通过 JOIN 一次取回),本类固定 4 条数据查询,不存在 N+1。
 */

namespace frontend\models;

use common\models\Game;
use common\models\Tournament;
use yii\db\Expression;
use yii\db\Query;

/**
 * 单届世界杯详情数据构建器。
 */
class TournamentDetail
{
    /**
     * 淘汰赛轮次到前端 key/label 的映射,数组顺序即页面展示顺序。
     */
    const BRACKET_STAGES = [
        Game::STAGE_ROUND_OF_32 => ['key' => 'r32', 'label' => '1/16决赛'],
        Game::STAGE_ROUND_OF_16 => ['key' => 'r16', 'label' => '1/8决赛'],
        Game::STAGE_QUARTER_FINAL => ['key' => 'qf', 'label' => '1/4决赛'],
        Game::STAGE_SEMI_FINAL => ['key' => 'sf', 'label' => '半决赛'],
        Game::STAGE_FINAL => ['key' => 'final', 'label' => '决赛'],
    ];

    /**
     * 冠军晋级路径的轮次顺序(不含三四名决赛)。
     */
    const KNOCKOUT_ORDER = [
        Game::STAGE_ROUND_OF_32 => 0,
        Game::STAGE_ROUND_OF_16 => 1,
        Game::STAGE_QUARTER_FINAL => 2,
        Game::STAGE_SEMI_FINAL => 3,
        Game::STAGE_FINAL => 4,
    ];

    /**
     * 构建单届详情页的完整数据契约。
     *
     * @param Tournament $tournament 已载入的届次 AR 对象
     * @return array
     */
    public static function build(Tournament $tournament)
    {
        $tournamentId = (int) $tournament->id;

        // 1) 参赛队 + 球队名称/代码(1 条查询)。
        $participants = self::loadParticipants($tournamentId);
        $teamCount = count($participants) > 0
            ? count($participants)
            : (int) $tournament->team_count;
        // 2) 该届全部比赛 + 主客队名称/代码(1 条查询)。
        $games = self::loadGames($tournamentId);
        // 3) 该届全部进球 + 球员/球队名称(1 条查询)。
        $goals = self::loadGoals($tournamentId);
        // 4) 本场留言数批量统计(1 条 GROUP BY 查询)。
        $commentCounts = self::loadCommentCounts(array_column($games, 'id'));

        $gameStatusById = [];
        $gamesByStage = [];
        foreach ($games as $game) {
            $gameStatusById[$game['id']] = $game['status'];
            $gamesByStage[$game['stage']][] = $game;
        }

        // 进球按场次归并,同时计算“非点球大战进球数”和“最大进球分钟”。
        $goalsByGame = [];
        $goalCountByGame = [];
        $maxMinuteByGame = [];
        foreach ($goals as $goal) {
            $gameId = $goal['game_id'];
            $goalsByGame[$gameId][] = $goal;
            if ($goal['is_shootout']) {
                continue;
            }
            $goalCountByGame[$gameId] = isset($goalCountByGame[$gameId])
                ? $goalCountByGame[$gameId] + 1
                : 1;
            if ($goal['minute'] !== null) {
                $maxMinuteByGame[$gameId] = isset($maxMinuteByGame[$gameId])
                    ? max($maxMinuteByGame[$gameId], $goal['minute'])
                    : $goal['minute'];
            }
        }

        $finalGame = self::lastStageGame($gamesByStage, Game::STAGE_FINAL);
        $thirdPlaceGame = self::lastStageGame($gamesByStage, Game::STAGE_THIRD_PLACE);

        $champion = null;
        $runnerUp = null;
        $championTeamId = null;
        if ($finalGame !== null && $finalGame['status'] === Game::STATUS_FINISHED) {
            $winnerSide = self::decideWinner($finalGame);
            if ($winnerSide !== null) {
                $loserSide = $winnerSide === 'home' ? 'away' : 'home';
                $champion = self::teamSummary($finalGame, $winnerSide);
                $runnerUp = self::teamSummary($finalGame, $loserSide);
                $championTeamId = $champion['team_id'];
            }
        }

        $thirdPlace = null;
        if ($thirdPlaceGame !== null && $thirdPlaceGame['status'] === Game::STATUS_FINISHED) {
            $thirdSide = self::decideWinner($thirdPlaceGame);
            if ($thirdSide !== null) {
                $thirdPlace = self::teamSummary($thirdPlaceGame, $thirdSide);
            }
        }

        $final = $finalGame === null ? null : self::buildFinal($finalGame, $goalsByGame, $maxMinuteByGame);

        $bracket = [];
        foreach (self::BRACKET_STAGES as $stage => $meta) {
            if (empty($gamesByStage[$stage])) {
                continue;
            }
            $matches = [];
            foreach ($gamesByStage[$stage] as $game) {
                $matches[] = self::buildMatchNode(
                    $game,
                    $goalsByGame,
                    $goalCountByGame,
                    $maxMinuteByGame,
                    $commentCounts
                );
            }
            $bracket[] = [
                'key' => $meta['key'],
                'label' => $meta['label'],
                'matches' => $matches,
            ];
        }

        $thirdPlaceMatch = $thirdPlaceGame === null ? null : self::buildMatchNode(
            $thirdPlaceGame,
            $goalsByGame,
            $goalCountByGame,
            $maxMinuteByGame,
            $commentCounts
        );

        $groups = self::buildGroups($participants, $games, $tournament, $teamCount);

        $stats = self::buildStats(
            $goals,
            $gameStatusById,
            $goalCountByGame,
            $teamCount
        );

        return [
            'tournament' => $tournament,
            'champion' => $champion,
            'runner_up' => $runnerUp,
            'third_place' => $thirdPlace,
            'final' => $final,
            'bracket' => $bracket,
            'third_place_match' => $thirdPlaceMatch,
            'groups' => $groups,
            'stats' => $stats,
            'champion_path' => self::buildChampionPath($games, $championTeamId),
        ];
    }

    /**
     * 读取该届参赛队,并一次 JOIN 出球队名称与代码。
     *
     * @param int $tournamentId
     * @return array
     */
    private static function loadParticipants($tournamentId)
    {
        $rows = (new Query())
            ->select([
                'team_id' => 'tt.team_id',
                'group_name' => 'tt.group_name',
                'name' => 'tm.name',
                'code' => 'tm.code',
            ])
            ->from(['tt' => '{{%tournament_team}}'])
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = tt.team_id')
            ->where(['tt.tournament_id' => $tournamentId])
            ->orderBy(['tt.group_name' => SORT_ASC, 'tt.fifa_rank' => SORT_ASC, 'tt.id' => SORT_ASC])
            ->all();

        $participants = [];
        foreach ($rows as $row) {
            $participants[] = [
                'team_id' => (int) $row['team_id'],
                'group_name' => $row['group_name'] !== null ? (string) $row['group_name'] : '',
                'name' => (string) $row['name'],
                'code' => $row['code'] !== null ? (string) $row['code'] : null,
            ];
        }

        return $participants;
    }

    /**
     * 读取该届全部比赛,并一次 JOIN 出主客队名称与代码。
     *
     * @param int $tournamentId
     * @return array
     */
    private static function loadGames($tournamentId)
    {
        $rows = (new Query())
            ->select([
                'id' => 'g.id',
                'stage' => 'g.stage',
                'status' => 'g.status',
                'home_team_id' => 'g.home_team_id',
                'away_team_id' => 'g.away_team_id',
                'home_score' => 'g.home_score',
                'away_score' => 'g.away_score',
                'home_penalty_score' => 'g.home_penalty_score',
                'away_penalty_score' => 'g.away_penalty_score',
                'match_time' => 'g.match_time',
                'venue' => 'g.venue',
                'home_name' => 'home.name',
                'home_code' => 'home.code',
                'away_name' => 'away.name',
                'away_code' => 'away.code',
            ])
            ->from(['g' => '{{%game}}'])
            ->innerJoin(['home' => '{{%team}}'], 'home.id = g.home_team_id')
            ->innerJoin(['away' => '{{%team}}'], 'away.id = g.away_team_id')
            ->where(['g.tournament_id' => $tournamentId])
            ->orderBy(['g.match_time' => SORT_ASC, 'g.id' => SORT_ASC])
            ->all();

        $games = [];
        foreach ($rows as $row) {
            $games[] = [
                'id' => (int) $row['id'],
                'stage' => (string) $row['stage'],
                'status' => (int) $row['status'],
                'home_team_id' => (int) $row['home_team_id'],
                'away_team_id' => (int) $row['away_team_id'],
                'home_score' => $row['home_score'] !== null ? (int) $row['home_score'] : null,
                'away_score' => $row['away_score'] !== null ? (int) $row['away_score'] : null,
                'home_penalty_score' => $row['home_penalty_score'] !== null
                    ? (int) $row['home_penalty_score'] : null,
                'away_penalty_score' => $row['away_penalty_score'] !== null
                    ? (int) $row['away_penalty_score'] : null,
                'match_time' => $row['match_time'] !== null ? (string) $row['match_time'] : null,
                'venue' => $row['venue'] !== null ? (string) $row['venue'] : null,
                'home_name' => (string) $row['home_name'],
                'home_code' => $row['home_code'] !== null ? (string) $row['home_code'] : null,
                'away_name' => (string) $row['away_name'],
                'away_code' => $row['away_code'] !== null ? (string) $row['away_code'] : null,
            ];
        }

        return $games;
    }

    /**
     * 读取该届全部进球,并一次 JOIN 出球员姓名与进球归属球队。
     *
     * @param int $tournamentId
     * @return array
     */
    private static function loadGoals($tournamentId)
    {
        $rows = (new Query())
            ->select([
                'id' => 'gl.id',
                'game_id' => 'gl.game_id',
                'player_id' => 'gl.player_id',
                'team_id' => 'gl.team_id',
                'minute' => 'gl.minute',
                'is_penalty' => 'gl.is_penalty',
                'is_own_goal' => 'gl.is_own_goal',
                'is_shootout' => 'gl.is_shootout',
                'player_name' => 'p.name',
                'team_name' => 'tm.name',
                'team_code' => 'tm.code',
            ])
            ->from(['gl' => '{{%goal}}'])
            ->innerJoin(['g' => '{{%game}}'], 'g.id = gl.game_id')
            ->leftJoin(['p' => '{{%player}}'], 'p.id = gl.player_id')
            ->innerJoin(['tm' => '{{%team}}'], 'tm.id = gl.team_id')
            ->where(['g.tournament_id' => $tournamentId])
            // 点球大战进球排在常规时间/加时进球之后,便于决赛时间线展示。
            ->orderBy([
                'gl.game_id' => SORT_ASC,
                'gl.is_shootout' => SORT_ASC,
                'gl.minute' => SORT_ASC,
                'gl.id' => SORT_ASC,
            ])
            ->all();

        $goals = [];
        foreach ($rows as $row) {
            $goals[] = [
                'id' => (int) $row['id'],
                'game_id' => (int) $row['game_id'],
                'player_id' => (int) $row['player_id'],
                'team_id' => (int) $row['team_id'],
                'minute' => $row['minute'] !== null ? (int) $row['minute'] : null,
                'is_penalty' => (int) $row['is_penalty'] === 1,
                'is_own_goal' => (int) $row['is_own_goal'] === 1,
                'is_shootout' => (int) $row['is_shootout'] === 1,
                'player_name' => $row['player_name'] !== null ? (string) $row['player_name'] : '未知球员',
                'team_name' => (string) $row['team_name'],
                'team_code' => $row['team_code'] !== null ? (string) $row['team_code'] : null,
            ];
        }

        return $goals;
    }

    /**
     * 批量统计比赛留言数:comment.target_type='game' 且 status=0。
     *
     * @param int[] $gameIds
     * @return array game_id => count
     */
    private static function loadCommentCounts(array $gameIds)
    {
        if ($gameIds === []) {
            return [];
        }

        $rows = (new Query())
            ->select([
                'target_id',
                'comment_count' => new Expression('COUNT(*)'),
            ])
            ->from('{{%comment}}')
            ->where([
                'target_type' => 'game',
                'target_id' => $gameIds,
                'status' => 0,
            ])
            ->groupBy('target_id')
            ->all();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['target_id']] = (int) $row['comment_count'];
        }

        return $counts;
    }

    /**
     * 取某阶段最后一场比赛(比赛已按时间升序)。
     *
     * @param array $gamesByStage
     * @param string $stage
     * @return array|null
     */
    private static function lastStageGame(array $gamesByStage, $stage)
    {
        if (empty($gamesByStage[$stage])) {
            return null;
        }

        return $gamesByStage[$stage][count($gamesByStage[$stage]) - 1];
    }

    /**
     * 判断比赛胜者。常规比分相等时比较点球大战比分;仍相等返回 null。
     *
     * @param array $game
     * @return string|null 'home'|'away'|null
     */
    private static function decideWinner(array $game)
    {
        $homeScore = $game['home_score'];
        $awayScore = $game['away_score'];
        if ($homeScore === null || $awayScore === null) {
            return null;
        }
        if ($homeScore > $awayScore) {
            return 'home';
        }
        if ($awayScore > $homeScore) {
            return 'away';
        }

        $homePenalty = $game['home_penalty_score'];
        $awayPenalty = $game['away_penalty_score'];
        if ($homePenalty !== null && $awayPenalty !== null) {
            if ($homePenalty > $awayPenalty) {
                return 'home';
            }
            if ($awayPenalty > $homePenalty) {
                return 'away';
            }
        }

        return null;
    }

    /**
     * 从比赛数组中取某一侧的球队摘要。
     *
     * @param array $game
     * @param string $side 'home'|'away'
     * @return array
     */
    private static function teamSummary(array $game, $side)
    {
        $isHome = $side === 'home';

        return [
            'team_id' => $isHome ? $game['home_team_id'] : $game['away_team_id'],
            'name' => $isHome ? $game['home_name'] : $game['away_name'],
            'code' => $isHome ? $game['home_code'] : $game['away_code'],
        ];
    }

    /**
     * 构建决赛详情。
     *
     * @param array $game
     * @param array $goalsByGame
     * @param array $maxMinuteByGame
     * @return array
     */
    private static function buildFinal(array $game, array $goalsByGame, array $maxMinuteByGame)
    {
        $finalGoals = [];
        foreach (isset($goalsByGame[$game['id']]) ? $goalsByGame[$game['id']] : [] as $goal) {
            $finalGoals[] = [
                'player' => $goal['player_name'],
                'team' => $goal['team_name'],
                'minute' => $goal['minute'],
                'is_penalty' => $goal['is_penalty'],
                'is_own_goal' => $goal['is_own_goal'],
                'is_shootout' => $goal['is_shootout'],
            ];
        }

        return [
            'id' => $game['id'],
            'date' => $game['match_time'],
            'home' => [
                'team_id' => $game['home_team_id'],
                'name' => $game['home_name'],
                'code' => $game['home_code'],
                'score' => $game['home_score'],
                'penalty' => $game['home_penalty_score'],
            ],
            'away' => [
                'team_id' => $game['away_team_id'],
                'name' => $game['away_name'],
                'code' => $game['away_code'],
                'score' => $game['away_score'],
                'penalty' => $game['away_penalty_score'],
            ],
            'extra_time' => self::isExtraTime($game, $maxMinuteByGame),
            'goals' => $finalGoals,
        ];
    }

    /**
     * 构建淘汰赛对阵节点。
     *
     * @param array $game
     * @param array $goalsByGame
     * @param array $goalCountByGame
     * @param array $maxMinuteByGame
     * @param array $commentCounts
     * @return array
     */
    private static function buildMatchNode(
        array $game,
        array $goalsByGame,
        array $goalCountByGame,
        array $maxMinuteByGame,
        array $commentCounts
    ) {
        $winnerSide = $game['status'] === Game::STATUS_FINISHED
            ? self::decideWinner($game)
            : null;
        $penalties = $game['home_penalty_score'] !== null && $game['away_penalty_score'] !== null;

        return [
            'id' => $game['id'],
            'stage' => $game['stage'],
            'home' => [
                'team_id' => $game['home_team_id'],
                'name' => $game['home_name'],
                'code' => $game['home_code'],
                'score' => $game['home_score'],
                'penalty' => $game['home_penalty_score'],
                'winner' => $winnerSide === 'home',
            ],
            'away' => [
                'team_id' => $game['away_team_id'],
                'name' => $game['away_name'],
                'code' => $game['away_code'],
                'score' => $game['away_score'],
                'penalty' => $game['away_penalty_score'],
                'winner' => $winnerSide === 'away',
            ],
            'extra_time' => self::isExtraTime($game, $maxMinuteByGame),
            'penalties' => $penalties,
            'goal_count' => isset($goalCountByGame[$game['id']]) ? $goalCountByGame[$game['id']] : 0,
            'comment_count' => isset($commentCounts[$game['id']]) ? $commentCounts[$game['id']] : 0,
            // 悬停展示用:保留全部进球(含乌龙、点球大战),按分钟升序,点球大战与 NULL 分钟排最后。
            'goals' => self::buildGoalTimeline(
                isset($goalsByGame[$game['id']]) ? $goalsByGame[$game['id']] : []
            ),
        ];
    }

    /**
     * 构建单场比赛的进球时间线。
     *
     * 排序规则:非点球大战在前、点球大战在后;同一组内按分钟升序,NULL 分钟排最后;
     * 分钟相同时按进球记录 id 升序。乌龙球/点球/点球大战标记原样保留。
     *
     * @param array $goals
     * @return array
     */
    private static function buildGoalTimeline(array $goals)
    {
        $timeline = [];
        foreach ($goals as $goal) {
            $timeline[] = [
                'player' => $goal['player_name'],
                'team' => $goal['team_name'],
                'minute' => $goal['minute'],
                'is_penalty' => $goal['is_penalty'],
                'is_own_goal' => $goal['is_own_goal'],
                'is_shootout' => $goal['is_shootout'],
                '_id' => $goal['id'],
            ];
        }

        usort($timeline, static function ($a, $b) {
            $aShootout = $a['is_shootout'] ? 1 : 0;
            $bShootout = $b['is_shootout'] ? 1 : 0;
            if ($aShootout !== $bShootout) {
                return $aShootout <=> $bShootout;
            }

            $aMinute = $a['minute'];
            $bMinute = $b['minute'];
            if ($aMinute === null && $bMinute === null) {
                return $a['_id'] <=> $b['_id'];
            }
            if ($aMinute === null) {
                return 1;
            }
            if ($bMinute === null) {
                return -1;
            }
            if ($aMinute !== $bMinute) {
                return $aMinute <=> $bMinute;
            }

            return $a['_id'] <=> $b['_id'];
        });

        foreach ($timeline as $index => $goal) {
            unset($timeline[$index]['_id']);
        }

        return array_values($timeline);
    }

    /**
     * 判断比赛是否进入加时:有点球大战必然加时;否则以非点球大战进球的最晚分钟判断。
     *
     * @param array $game
     * @param array $maxMinuteByGame
     * @return bool
     */
    private static function isExtraTime(array $game, array $maxMinuteByGame)
    {
        if ($game['home_penalty_score'] !== null && $game['away_penalty_score'] !== null) {
            return true;
        }

        $maxMinute = isset($maxMinuteByGame[$game['id']]) ? $maxMinuteByGame[$game['id']] : null;

        return $maxMinute !== null && $maxMinute > 90;
    }

    /**
     * 构建小组积分榜。
     *
     * 积分口径:仅统计 stage='小组赛' 且 status=2 的比赛;胜场积分取 tournament.win_points,
     * 平局固定 1 分。排序依次比较积分、净胜球、进球数、球队编号。
     * 晋级规则:每组前 2 直接晋级;24 队制再取 4 个成绩最好的小组第 3,
     * 48 队制再取 8 个成绩最好的小组第 3,32 队制无最佳第 3 名额。
     *
     * @param array $participants
     * @param array $games
     * @param Tournament $tournament
     * @param int $teamCount
     * @return array
     */
    private static function buildGroups(array $participants, array $games, Tournament $tournament, $teamCount)
    {
        $winPoints = $tournament->win_points !== null ? (int) $tournament->win_points : 3;
        $groupRows = [];
        $teamGroup = [];

        foreach ($participants as $participant) {
            $groupName = trim($participant['group_name']);
            if ($groupName === '') {
                $groupName = '未分组';
            }
            $teamId = $participant['team_id'];
            $groupRows[$groupName][$teamId] = [
                'team_id' => $teamId,
                'name' => $participant['name'],
                'code' => $participant['code'],
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
                'qualified' => false,
            ];
            $teamGroup[$teamId] = $groupName;
        }

        foreach ($games as $game) {
            if ($game['stage'] !== Game::STAGE_GROUP
                || $game['status'] !== Game::STATUS_FINISHED
                || $game['home_score'] === null
                || $game['away_score'] === null) {
                continue;
            }

            $homeId = $game['home_team_id'];
            $awayId = $game['away_team_id'];
            if (!isset($teamGroup[$homeId], $teamGroup[$awayId])) {
                continue;
            }

            $homeGroup = $teamGroup[$homeId];
            $awayGroup = $teamGroup[$awayId];
            $homeScore = $game['home_score'];
            $awayScore = $game['away_score'];

            $groupRows[$homeGroup][$homeId]['played']++;
            $groupRows[$awayGroup][$awayId]['played']++;
            $groupRows[$homeGroup][$homeId]['goals_for'] += $homeScore;
            $groupRows[$homeGroup][$homeId]['goals_against'] += $awayScore;
            $groupRows[$awayGroup][$awayId]['goals_for'] += $awayScore;
            $groupRows[$awayGroup][$awayId]['goals_against'] += $homeScore;

            if ($homeScore > $awayScore) {
                $groupRows[$homeGroup][$homeId]['won']++;
                $groupRows[$awayGroup][$awayId]['lost']++;
            } elseif ($awayScore > $homeScore) {
                $groupRows[$awayGroup][$awayId]['won']++;
                $groupRows[$homeGroup][$homeId]['lost']++;
            } else {
                $groupRows[$homeGroup][$homeId]['drawn']++;
                $groupRows[$awayGroup][$awayId]['drawn']++;
            }
        }

        foreach (array_keys($groupRows) as $groupName) {
            $rows = array_values($groupRows[$groupName]);
            foreach ($rows as $index => $row) {
                $rows[$index]['points'] = $row['won'] * $winPoints + $row['drawn'];
                $rows[$index]['goal_difference'] = $row['goals_for'] - $row['goals_against'];
            }
            usort($rows, [self::class, 'compareGroupRows']);
            foreach ($rows as $index => $row) {
                $rows[$index]['qualified'] = $index < 2;
            }
            $groupRows[$groupName] = $rows;
        }

        // 24 队制(6 组)取 4 个最佳第 3;48 队制(12 组)取 8 个最佳第 3。
        $bestThirdSlots = 0;
        if ($teamCount === 24) {
            $bestThirdSlots = 4;
        } elseif ($teamCount === 48) {
            $bestThirdSlots = 8;
        }
        if ($bestThirdSlots > 0) {
            $thirdCandidates = [];
            foreach ($groupRows as $groupName => $rows) {
                if ($groupName === '未分组' || !isset($rows[2])) {
                    continue;
                }
                $thirdCandidates[] = [
                    'group' => $groupName,
                    'row' => $rows[2],
                ];
            }
            usort($thirdCandidates, static function ($a, $b) {
                return self::compareGroupRows($a['row'], $b['row']);
            });
            $take = min($bestThirdSlots, count($thirdCandidates));
            for ($i = 0; $i < $take; $i++) {
                $groupName = $thirdCandidates[$i]['group'];
                $groupRows[$groupName][2]['qualified'] = true;
            }
        }

        $groupNames = array_keys($groupRows);
        usort($groupNames, static function ($a, $b) {
            if ($a === '未分组') {
                return 1;
            }
            if ($b === '未分组') {
                return -1;
            }

            return strnatcasecmp($a, $b);
        });

        $groups = [];
        foreach ($groupNames as $groupName) {
            $groups[] = [
                'group' => $groupName,
                'rows' => $groupRows[$groupName],
            ];
        }

        return $groups;
    }

    /**
     * 小组排名比较:积分 > 净胜球 > 进球数 > 球队编号。
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private static function compareGroupRows(array $a, array $b)
    {
        if ($a['points'] !== $b['points']) {
            return $b['points'] <=> $a['points'];
        }
        if ($a['goal_difference'] !== $b['goal_difference']) {
            return $b['goal_difference'] <=> $a['goal_difference'];
        }
        if ($a['goals_for'] !== $b['goals_for']) {
            return $b['goals_for'] <=> $a['goals_for'];
        }

        return $a['team_id'] <=> $b['team_id'];
    }

    /**
     * 构建赛事统计与射手王。
     *
     * @param array $goals
     * @param array $gameStatusById
     * @param array $goalCountByGame
     * @param int $teamCount
     * @return array
     */
    private static function buildStats(array $goals, array $gameStatusById, array $goalCountByGame, $teamCount)
    {
        $matchCount = 0;
        $goalCount = 0;
        foreach ($gameStatusById as $gameId => $status) {
            if ($status !== Game::STATUS_FINISHED) {
                continue;
            }
            $matchCount++;
            $goalCount += isset($goalCountByGame[$gameId]) ? $goalCountByGame[$gameId] : 0;
        }

        // 射手王:排除乌龙球与点球大战进球,只统计已结束比赛;按球员+球队归并。
        $scorers = [];
        foreach ($goals as $goal) {
            if ($goal['is_own_goal'] || $goal['is_shootout']) {
                continue;
            }
            if (!isset($gameStatusById[$goal['game_id']])
                || $gameStatusById[$goal['game_id']] !== Game::STATUS_FINISHED) {
                continue;
            }
            $key = $goal['player_id'] . ':' . $goal['team_id'];
            if (!isset($scorers[$key])) {
                $scorers[$key] = [
                    'player_id' => $goal['player_id'],
                    'name' => $goal['player_name'],
                    'team' => $goal['team_name'],
                    'goals' => 0,
                ];
            }
            $scorers[$key]['goals']++;
        }

        $topScorer = null;
        foreach ($scorers as $scorer) {
            if ($topScorer === null
                || $scorer['goals'] > $topScorer['goals']
                || ($scorer['goals'] === $topScorer['goals']
                    && $scorer['player_id'] < $topScorer['player_id'])) {
                $topScorer = $scorer;
            }
        }

        return [
            'team_count' => $teamCount,
            'match_count' => $matchCount,
            'goal_count' => $goalCount,
            'avg_goals' => $matchCount > 0 ? round($goalCount / $matchCount, 2) : 0,
            'top_scorer' => $topScorer,
        ];
    }

    /**
     * 构建冠军晋级路径:从冠军参加的第一场淘汰赛开始,比分统一按冠军视角排列。
     *
     * @param array $games
     * @param int|null $championTeamId
     * @return array
     */
    private static function buildChampionPath(array $games, $championTeamId)
    {
        if ($championTeamId === null) {
            return [];
        }

        $path = [];
        foreach ($games as $game) {
            if (!isset(self::KNOCKOUT_ORDER[$game['stage']])) {
                continue;
            }
            if ($game['home_team_id'] !== $championTeamId
                && $game['away_team_id'] !== $championTeamId) {
                continue;
            }

            $championIsHome = $game['home_team_id'] === $championTeamId;
            $championScore = $championIsHome ? $game['home_score'] : $game['away_score'];
            $opponentScore = $championIsHome ? $game['away_score'] : $game['home_score'];
            $championPenalty = $championIsHome
                ? $game['home_penalty_score'] : $game['away_penalty_score'];
            $opponentPenalty = $championIsHome
                ? $game['away_penalty_score'] : $game['home_penalty_score'];

            $path[] = [
                'stage' => $game['stage'],
                'opponent' => $championIsHome
                    ? ['name' => $game['away_name'], 'code' => $game['away_code']]
                    : ['name' => $game['home_name'], 'code' => $game['home_code']],
                'score' => $championScore !== null && $opponentScore !== null
                    ? $championScore . '-' . $opponentScore : '-',
                'penalty' => $championPenalty !== null && $opponentPenalty !== null
                    ? $championPenalty . '-' . $opponentPenalty : null,
                'match_id' => $game['id'],
                '_order' => self::KNOCKOUT_ORDER[$game['stage']],
                '_time' => (string) $game['match_time'],
            ];
        }

        usort($path, static function ($a, $b) {
            if ($a['_order'] !== $b['_order']) {
                return $a['_order'] <=> $b['_order'];
            }

            return strcmp($a['_time'], $b['_time']);
        });

        foreach ($path as $index => $item) {
            unset($path[$index]['_order'], $path[$index]['_time']);
        }

        return array_values($path);
    }
}
