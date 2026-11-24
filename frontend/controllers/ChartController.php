<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 动态图 JSON 接口(积分榜 / 射手榜 / 各队进球分布)。
 */

namespace frontend\controllers;

use Yii;
use common\components\TeamFlag;
use yii\db\Query;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ChartController extends Controller
{
    /**
     * 所有动作统一返回 JSON,异常响应也走 JSON。
     */
    public function beforeAction($action)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        return parent::beforeAction($action);
    }

    /**
     * 积分榜:只统计 stage='小组赛' 且 status=2 的比赛。
     * 胜场积分取自 tournament.win_points(1986/1990 为 2 分,其余为 3 分)。
     *
     * @param int|string|null $tournamentId 届次 id
     * @param string|null $group 可选小组名,如 A
     * @return array
     */
    public function actionStandings($tournamentId = null, $group = null)
    {
        list($tournamentId, $winPoints) = $this->resolveTournament($tournamentId);
        $group = $this->normalizeGroup($group);

        // 主队视角与客队视角合并成一张“每队每场”的临时结果集。
        $homeMatches = (new Query())
            ->select([
                'team_id' => 'g.home_team_id',
                'goals_for' => 'g.home_score',
                'goals_against' => 'g.away_score',
            ])
            ->from(['g' => '{{%game}}'])
            ->where([
                'g.tournament_id' => $tournamentId,
                'g.stage' => '小组赛',
                'g.status' => 2,
            ]);

        $awayMatches = (new Query())
            ->select([
                'team_id' => 'g.away_team_id',
                'goals_for' => 'g.away_score',
                'goals_against' => 'g.home_score',
            ])
            ->from(['g' => '{{%game}}'])
            ->where([
                'g.tournament_id' => $tournamentId,
                'g.stage' => '小组赛',
                'g.status' => 2,
            ]);

        $matches = $homeMatches->union($awayMatches, true);

        $query = (new Query())
            ->select([
                'group_name' => 'tt.group_name',
                'team_id' => 'tt.team_id',
                'team_name' => 't.name',
                'team_code' => 't.code',
                'played' => 'COUNT(m.team_id)',
                'won' => 'COALESCE(SUM(CASE WHEN m.goals_for > m.goals_against THEN 1 ELSE 0 END), 0)',
                'drawn' => 'COALESCE(SUM(CASE WHEN m.goals_for = m.goals_against THEN 1 ELSE 0 END), 0)',
                'lost' => 'COALESCE(SUM(CASE WHEN m.goals_for < m.goals_against THEN 1 ELSE 0 END), 0)',
                'goals_for' => 'COALESCE(SUM(m.goals_for), 0)',
                'goals_against' => 'COALESCE(SUM(m.goals_against), 0)',
            ])
            ->from(['tt' => '{{%tournament_team}}'])
            ->innerJoin(['t' => '{{%team}}'], 't.id = tt.team_id')
            ->innerJoin(['m' => $matches], 'm.team_id = tt.team_id')
            ->where(['tt.tournament_id' => $tournamentId]);

        if ($group !== null) {
            $query->andWhere(['tt.group_name' => $group]);
        }

        $rows = $query
            ->groupBy(['tt.group_name', 'tt.team_id', 't.name', 't.code'])
            ->orderBy(['tt.group_name' => SORT_ASC, 'tt.team_id' => SORT_ASC])
            ->all();

        $groups = [];
        foreach ($rows as $row) {
            $won = (int) $row['won'];
            $drawn = (int) $row['drawn'];
            $goalsFor = (int) $row['goals_for'];
            $goalsAgainst = (int) $row['goals_against'];
            $groupName = ($row['group_name'] === null || $row['group_name'] === '')
                ? '未分组'
                : (string) $row['group_name'];

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = ['group' => $groupName, 'rows' => []];
            }

            $groups[$groupName]['rows'][] = [
                'teamId' => (int) $row['team_id'],
                'teamName' => (string) $row['team_name'],
                'teamCode' => ($row['team_code'] === null) ? '' : (string) $row['team_code'],
                'teamFlag' => TeamFlag::url($row['team_code']),
                'played' => (int) $row['played'],
                'won' => $won,
                'drawn' => $drawn,
                'lost' => (int) $row['lost'],
                'goalsFor' => $goalsFor,
                'goalsAgainst' => $goalsAgainst,
                'goalDifference' => $goalsFor - $goalsAgainst,
                'points' => $won * $winPoints + $drawn,
            ];
        }

        foreach ($groups as &$groupData) {
            usort($groupData['rows'], [$this, 'compareStandingRows']);
            foreach ($groupData['rows'] as $index => &$standingRow) {
                $standingRow['rank'] = $index + 1;
            }
            unset($standingRow);
        }
        unset($groupData);

        ksort($groups, SORT_STRING);

        return [
            'tournamentId' => $tournamentId,
            'winPoints' => $winPoints,
            'group' => $group,
            'groups' => array_values($groups),
        ];
    }

    /**
     * 射手榜:排除点球大战进球与乌龙球,按球员分组取前 10。
     *
     * @param int|string|null $tournamentId 届次 id
     * @return array
     */
    public function actionScorers($tournamentId = null)
    {
        list($tournamentId) = $this->resolveTournament($tournamentId);

        $rows = (new Query())
            ->select([
                'player_id' => 'g.player_id',
                'player_name' => 'p.name',
                'player_name_zh' => 'p.name_zh',
                'player_nationality' => 'p.nationality',
                'team_id' => 'g.team_id',
                'team_name' => 't.name',
                'team_code' => 't.code',
                'goals' => 'COUNT(g.id)',
            ])
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['p' => '{{%player}}'], 'p.id = g.player_id')
            ->innerJoin(['t' => '{{%team}}'], 't.id = g.team_id')
            ->innerJoin(['gm' => '{{%game}}'], 'gm.id = g.game_id')
            ->where([
                'gm.tournament_id' => $tournamentId,
                'g.is_shootout' => 0,
                'g.is_own_goal' => 0,
            ])
            ->groupBy(['g.player_id', 'p.name', 'p.name_zh', 'p.nationality', 'g.team_id', 't.name', 't.code'])
            ->orderBy(['goals' => SORT_DESC, 'p.name' => SORT_ASC])
            ->limit(10)
            ->all();

        $scorers = [];
        foreach ($rows as $index => $row) {
            $scorers[] = [
                'rank' => $index + 1,
                'playerId' => (int) $row['player_id'],
                'playerName' => (string) $row['player_name'],
                'playerNameZh' => (string) ($row['player_name_zh'] ?: ''),
                'alwaysZh' => in_array($row['player_nationality'], ['中国', '中国台北', '中国香港', '中国澳门'], true),
                'teamId' => (int) $row['team_id'],
                'teamName' => (string) $row['team_name'],
                'teamCode' => (string) ($row['team_code'] ?? ''),
                'teamFlag' => TeamFlag::url($row['team_code'] ?? ''),
                'goals' => (int) $row['goals'],
            ];
        }

        return [
            'tournamentId' => $tournamentId,
            'scorers' => $scorers,
        ];
    }

    /**
     * 各队进球分布:按 goal.team_id 分组,排除点球大战进球。
     * 乌龙球的 team_id 已经是受益方,因此这里不再排除 is_own_goal。
     *
     * @param int|string|null $tournamentId 届次 id
     * @return array
     */
    public function actionTeamGoals($tournamentId = null)
    {
        list($tournamentId) = $this->resolveTournament($tournamentId);

        $rows = (new Query())
            ->select([
                'team_id' => 'g.team_id',
                'team_name' => 't.name',
                'team_code' => 't.code',
                'goals' => 'COUNT(g.id)',
            ])
            ->from(['g' => '{{%goal}}'])
            ->innerJoin(['t' => '{{%team}}'], 't.id = g.team_id')
            ->innerJoin(['gm' => '{{%game}}'], 'gm.id = g.game_id')
            ->where([
                'gm.tournament_id' => $tournamentId,
                'g.is_shootout' => 0,
            ])
            ->groupBy(['g.team_id', 't.name'])
            ->orderBy(['goals' => SORT_DESC, 't.name' => SORT_ASC])
            ->all();

        $teams = [];
        $totalGoals = 0;
        foreach ($rows as $row) {
            $goals = (int) $row['goals'];
            $totalGoals += $goals;
            $teams[] = [
                'teamId' => (int) $row['team_id'],
                'teamName' => (string) $row['team_name'],
                'teamCode' => (string) ($row['team_code'] ?? ''),
                'teamFlag' => TeamFlag::url($row['team_code'] ?? ''),
                'goals' => $goals,
            ];
        }

        return [
            'tournamentId' => $tournamentId,
            'totalGoals' => $totalGoals,
            'teams' => $teams,
        ];
    }

    /**
     * 校验 tournamentId 并返回 [id, win_points]。
     *
     * @param mixed $tournamentId
     * @return array
     * @throws BadRequestHttpException 参数缺失或格式错误
     * @throws NotFoundHttpException 届次不存在
     */
    private function resolveTournament($tournamentId)
    {
        if ($tournamentId === null || $tournamentId === '') {
            throw new BadRequestHttpException('缺少必填参数 tournamentId。');
        }

        if (!is_scalar($tournamentId) || !ctype_digit(trim((string) $tournamentId))) {
            throw new BadRequestHttpException('参数 tournamentId 必须是正整数。');
        }

        $tournamentId = (int) trim((string) $tournamentId);
        if ($tournamentId < 1) {
            throw new BadRequestHttpException('参数 tournamentId 必须是正整数。');
        }

        $tournament = (new Query())
            ->select(['id', 'win_points'])
            ->from(['t' => '{{%tournament}}'])
            ->where(['t.id' => $tournamentId])
            ->one();

        if ($tournament === false || $tournament === null) {
            throw new NotFoundHttpException('未找到该届次。');
        }

        return [(int) $tournament['id'], (int) $tournament['win_points']];
    }

    /**
     * 小组参数只接受短字符串;空值表示不筛选。
     *
     * @param mixed $group
     * @return string|null
     * @throws BadRequestHttpException
     */
    private function normalizeGroup($group)
    {
        if ($group === null || $group === '') {
            return null;
        }

        if (!is_scalar($group)) {
            throw new BadRequestHttpException('参数 group 必须是字符串。');
        }

        $group = trim((string) $group);
        if ($group === '') {
            return null;
        }

        if (mb_strlen($group) > 10) {
            throw new BadRequestHttpException('参数 group 过长。');
        }

        return $group;
    }

    /**
     * 组内排序:积分 > 净胜球 > 进球数 > 队名。
     *
     * @param array $a
     * @param array $b
     * @return int
     */
    private function compareStandingRows(array $a, array $b)
    {
        if ($a['points'] !== $b['points']) {
            return $b['points'] <=> $a['points'];
        }
        if ($a['goalDifference'] !== $b['goalDifference']) {
            return $b['goalDifference'] <=> $a['goalDifference'];
        }
        if ($a['goalsFor'] !== $b['goalsFor']) {
            return $b['goalsFor'] <=> $a['goalsFor'];
        }

        return strcmp($a['teamName'], $b['teamName']);
    }
}
