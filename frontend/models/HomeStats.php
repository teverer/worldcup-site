<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261117
 * 模块:界面设计 —— 首页数据统计查询
 *
 * 提供首页与「关于本站」共用的概览统计；视图只负责渲染，
 * 避免在模板中拼接复杂 SQL。
 */

namespace frontend\models;

use common\models\Game;
use common\models\Goal;
use common\models\Team;
use common\models\Tournament;

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
}
