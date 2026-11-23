<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261117
 * 模块:界面设计 —— 首页数据统计查询
 *
 * 集中提供首页所需的概览统计、最近比赛、射手榜、历届世界杯与最新资讯,
 * 视图只负责渲染,避免在模板中拼接复杂 SQL。
 */

namespace frontend\models;

use common\models\Game;
use common\models\Goal;
use common\models\News;
use common\models\Player;
use common\models\Team;
use common\models\Tournament;
use yii\db\Expression;
use yii\db\Query;

/**
 * 首页数据查询辅助类。
 */
class HomeStats
{
    /**
     * 首页数据概览:届次、球队、比赛、进球总数。
     *
     * @return array{tournaments:int, teams:int, games:int, goals:int}
     */
    public static function overview()
    {
        return [
            'tournaments' => (int) Tournament::find()->count(),
            'teams' => (int) Team::find()->count(),
            'games' => (int) Game::find()->count(),
            // 与射手榜口径一致:点球大战进球不计入赛事总进球;乌龙球仍计入。
            'goals' => (int) Goal::find()->where(['is_shootout' => 0])->count(),
        ];
    }

    /**
     * 最近已结束的比赛,按开球时间倒序。
     *
     * @param int $limit 返回场次
     * @return Game[]
     */
    public static function recentGames($limit = 6)
    {
        return Game::find()
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->where(['status' => Game::STATUS_FINISHED])
            ->orderBy(['match_time' => SORT_DESC, 'id' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * 最近一届有已结束比赛的届次,用于首页射手榜默认展示。
     *
     * 按年份倒序动态选取,不写死届次编号;若所有届次均无已结束比赛,
     * 返回 null,由视图展示空状态。
     *
     * @return Tournament|null
     */
    public static function latestFinishedTournament()
    {
        return Tournament::find()
            ->alias('t')
            ->innerJoin(['g' => Game::tableName()], 'g.tournament_id = t.id')
            ->where(['g.status' => Game::STATUS_FINISHED])
            ->orderBy(['t.year' => SORT_DESC, 't.id' => SORT_DESC])
            ->one();
    }

    /**
     * 单届射手榜:排除乌龙球与点球大战进球,按进球数倒序。
     *
     * goal.team_id 记录进球归属球队(乌龙球填受益方),因此排除乌龙球后
     * 可直接用它关联球员所属球队。
     *
     * @param int|null $tournamentId 届次编号;为 null 时不限定届次
     * @param int $limit 返回人数
     * @return array 每项包含 player_id、player_name、team_name、goals
     */
    public static function topScorers($tournamentId = null, $limit = 5)
    {
        $query = (new Query())
            ->select([
                'player_id' => 'g.player_id',
                'player_name' => new Expression("COALESCE(NULLIF(p.name_zh, ''), p.name)"),
                'team_name' => 'MAX(t.name)',
                'goals' => 'COUNT(*)',
            ])
            ->from(['g' => Goal::tableName()])
            ->innerJoin(['gm' => Game::tableName()], 'gm.id = g.game_id')
            ->innerJoin(['p' => Player::tableName()], 'p.id = g.player_id')
            ->innerJoin(['t' => Team::tableName()], 't.id = g.team_id')
            ->where(['g.is_own_goal' => 0, 'g.is_shootout' => 0]);

        if ($tournamentId !== null) {
            $query->andWhere(['gm.tournament_id' => (int) $tournamentId]);
        }

        return $query
            ->groupBy(['g.player_id', 'p.name', 'p.name_zh'])
            ->orderBy(['goals' => SORT_DESC, 'player_name' => SORT_ASC])
            ->limit($limit)
            ->all();
    }

    /**
     * 最近若干届世界杯。
     *
     * @param int $limit 返回届数
     * @return Tournament[]
     */
    public static function recentTournaments($limit = 4)
    {
        return Tournament::find()
            ->orderBy(['year' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * 最新已发布资讯。
     *
     * @param int $limit 返回条数
     * @return News[]
     */
    public static function latestNews($limit = 3)
    {
        return News::findPublished()
            ->limit($limit)
            ->all();
    }
}
