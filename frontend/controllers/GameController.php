<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 比赛列表筛选、比赛详情与进球时间线。
 */

namespace frontend\controllers;

use Yii;
use common\models\Game;
use common\models\Player;
use common\models\Tournament;
use common\models\TournamentTeam;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class GameController extends Controller
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

    public const STATUSES = [
        0 => '未赛',
        1 => '进行中',
        2 => '已结束',
    ];

    /**
     * 比赛列表：支持按届次、阶段、小组筛选并分页。
     *
     * @return string
     */
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $tournamentId = $this->normalizeId($request->get('tournamentId'));
        $stage = $this->normalizeStage($request->get('stage'));
        $group = $this->normalizeGroup($request->get('group'));

        $query = Game::find()
            ->alias('g')
            ->with(['tournament', 'homeTeam', 'awayTeam'])
            ->orderBy(['g.match_time' => SORT_DESC, 'g.id' => SORT_DESC]);

        if ($tournamentId !== null) {
            $query->andWhere(['g.tournament_id' => $tournamentId]);
        }
        if ($stage !== null) {
            $query->andWhere(['g.stage' => $stage]);
        }
        if ($group !== null) {
            // game 表没有小组字段，按主队 + 届次关联 tournament_team 取 group_name。
            $query->innerJoin(
                ['tt' => TournamentTeam::tableName()],
                'tt.tournament_id = g.tournament_id AND tt.team_id = g.home_team_id'
            )
                ->andWhere(['tt.group_name' => $group])
                ->andWhere(['g.stage' => '小组赛']);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => false,
        ]);

        $tournamentOptions = ArrayHelper::map(
            Tournament::find()->orderBy(['year' => SORT_DESC, 'id' => SORT_DESC])->all(),
            'id',
            'name'
        );

        $groupQuery = TournamentTeam::find()
            ->select('group_name')
            ->distinct()
            ->where(['not', ['group_name' => null]])
            ->andWhere(['<>', 'group_name', ''])
            ->orderBy(['group_name' => SORT_ASC]);
        if ($tournamentId !== null) {
            $groupQuery->andWhere(['tournament_id' => $tournamentId]);
        }
        $groupNames = array_values(array_filter($groupQuery->column(), static function ($name) {
            return is_string($name) && $name !== '';
        }));
        $groupOptions = $groupNames ? array_combine($groupNames, $groupNames) : [];

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'tournamentId' => $tournamentId,
            'stage' => $stage,
            'group' => $group,
            'tournamentOptions' => $tournamentOptions,
            'stageOptions' => array_combine(self::STAGES, self::STAGES),
            'groupOptions' => $groupOptions,
            'statusLabels' => self::STATUSES,
        ]);
    }

    /**
     * 比赛详情：基本信息、比分与点球、进球时间线及留言区。
     *
     * @param mixed $id
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        $gameId = $this->normalizeId($id);
        if ($gameId === null) {
            throw new NotFoundHttpException('比赛不存在。');
        }

        $model = Game::find()
            ->with(['tournament', 'homeTeam', 'awayTeam'])
            ->where(['id' => $gameId])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException('比赛不存在。');
        }

        $goals = $model->getGoals()
            ->orderBy(['is_shootout' => SORT_ASC, 'minute' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $playerIds = [];
        foreach ($goals as $goal) {
            $playerIds[] = (int) $goal->player_id;
            if ($goal->assist_player_id !== null) {
                $playerIds[] = (int) $goal->assist_player_id;
            }
        }
        $playerIds = array_values(array_unique(array_filter($playerIds)));
        $players = $playerIds
            ? Player::find()->where(['id' => $playerIds])->indexBy('id')->all()
            : [];

        return $this->render('view', [
            'model' => $model,
            'goals' => $goals,
            'players' => $players,
            'statusLabels' => self::STATUSES,
        ]);
    }

    /**
     * 只接受正整数，其余一律视为未传入。
     *
     * @param mixed $value
     * @return int|null
     */
    private function normalizeId($value)
    {
        if (!is_scalar($value) || !ctype_digit((string) $value) || (int) $value < 1) {
            return null;
        }

        return (int) $value;
    }

    /**
     * 阶段必须是预定义值之一。
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizeStage($value)
    {
        if (!is_string($value) || !in_array($value, self::STAGES, true)) {
            return null;
        }

        return $value;
    }

    /**
     * 小组名只允许 1~10 位字母或数字。
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizeGroup($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || !preg_match('/^[A-Za-z0-9]{1,10}$/', $value)) {
            return null;
        }

        return $value;
    }
}
