<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 届次列表与单届详情页数据准备。
 */

namespace frontend\controllers;

use common\models\Team;
use common\models\Tournament;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class TournamentController extends Controller
{
    public const STAGES = [
        '小组赛',
        '1/16决赛',
        '1/8决赛',
        '1/4决赛',
        '半决赛',
        '三四名决赛',
        '决赛',
    ];

    /**
     * 历届世界杯列表。
     *
     * @return string
     */
    public function actionIndex()
    {
        $tournaments = Tournament::find()
            ->orderBy(['year' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        return $this->render('index', ['tournaments' => $tournaments]);
    }

    /**
     * 单届详情：基本信息、分组参赛队、分阶段赛程与动态图表。
     *
     * @param mixed $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('届次不存在。');
        }

        $model = Tournament::findOne((int) $id);
        if ($model === null) {
            throw new NotFoundHttpException('届次不存在。');
        }

        $participants = $model->getTournamentTeams()
            ->orderBy(['group_name' => SORT_ASC, 'fifa_rank' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $teamIds = array_values(array_unique(array_map(static function ($participant) {
            return (int) $participant->team_id;
        }, $participants)));
        $teams = $teamIds
            ? Team::find()->where(['id' => $teamIds])->indexBy('id')->all()
            : [];

        $groups = [];
        foreach ($participants as $participant) {
            $groupName = $participant->group_name !== null && $participant->group_name !== ''
                ? (string) $participant->group_name
                : '未分组';
            $groups[$groupName][] = $participant;
        }
        $groupNames = array_keys($groups);
        sort($groupNames, SORT_NATURAL | SORT_FLAG_CASE);
        $orderedGroups = [];
        foreach ($groupNames as $groupName) {
            $orderedGroups[$groupName] = $groups[$groupName];
        }

        $games = $model->getGames()
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy(['match_time' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $gamesByStage = [];
        foreach ($games as $game) {
            $stage = $game->stage !== null && $game->stage !== '' ? (string) $game->stage : '其他';
            $gamesByStage[$stage][] = $game;
        }
        $orderedGames = [];
        foreach (self::STAGES as $stage) {
            if (isset($gamesByStage[$stage])) {
                $orderedGames[$stage] = $gamesByStage[$stage];
                unset($gamesByStage[$stage]);
            }
        }
        foreach ($gamesByStage as $stage => $stageGames) {
            $orderedGames[$stage] = $stageGames;
        }

        return $this->render('view', [
            'model' => $model,
            'groups' => $orderedGroups,
            'teams' => $teams,
            'gamesByStage' => $orderedGames,
            'statusLabels' => GameController::STATUSES,
        ]);
    }
}
