<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 动态图 JSON 接口(进球时间分布 / 射手榜 / 各队进球分布；积分榜保留备用)。
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



}
