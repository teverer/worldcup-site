<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 高家铭 2412267, 20261021
 * 模块:球队与球员 —— 公开球队目录与按届次筛选的名单。
 */

namespace frontend\controllers;

use Yii;
use common\models\Team;
use common\models\TeamSearch;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class TeamController extends Controller {
    public function actionIndex() {
        $searchModel = new TeamSearch();
        $searchModel->load(Yii::$app->request->queryParams, '');
        $searchModel->validate();

        // 83 支球队一屏放得下,按大洲分组比「拼音分页表」更适合浏览。
        $query = Team::find()->orderBy(['name' => SORT_ASC]);
        $query->andFilterWhere(['or',
            ['like', 'name', $searchModel->q],
            ['like', 'code', $searchModel->q],
        ]);
        $query->andFilterWhere(['continent' => $searchModel->continent]);

        $groups = [];
        foreach ($query->all() as $team) {
            $continent = $team->continent ?: '其他';
            $groups[$continent][] = $team;
        }

        $ordered = [];
        foreach (array_merge(Team::CONTINENTS, ['其他']) as $continent) {
            if (!empty($groups[$continent])) {
                $ordered[$continent] = $groups[$continent];
            }
            unset($groups[$continent]);
        }
        // 数据里若有未知大洲,放在最后,不要静默丢掉。
        foreach ($groups as $continent => $teams) {
            $ordered[$continent] = $teams;
        }

        $hasFilter = trim((string) $searchModel->q) !== ''
            || ($searchModel->continent !== null && $searchModel->continent !== '');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'groups' => $ordered,
            'total' => (int) Team::find()->count(),
            'hasFilter' => $hasFilter,
        ]);
    }

    public function actionView($id, $tournamentId = null) {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('球队不存在。');
        }
        $model = Team::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('球队不存在。');
        }
        $tournaments = $model->tournaments();
        $allRoster = $tournamentId === 'all';
        $selectedTournamentId = null;
        if (!$allRoster && $tournamentId !== null && $tournamentId !== '') {
            if (!is_scalar($tournamentId) || !ctype_digit((string) $tournamentId)
                || !in_array((int) $tournamentId, array_map('intval', array_column($tournaments, 'id')), true)) {
                throw new NotFoundHttpException('该球队没有此届参赛名单。');
            }
            $selectedTournamentId = (int) $tournamentId;
        }

        // 默认看最近一届的当届阵容;想看所有届次去重,显式点「全部届次」。
        $rosterTournamentId = $allRoster
            ? null
            : ($selectedTournamentId !== null
                ? $selectedTournamentId
                : ($tournaments ? (int) $tournaments[0]['id'] : null));

        return $this->render('view', [
            'model' => $model,
            'tournaments' => $tournaments,
            'tournamentId' => $allRoster ? 'all' : $selectedTournamentId,
            'rosterTournamentId' => $rosterTournamentId,
            'allRoster' => $allRoster,
            'records' => $model->tournamentRecords(),
            'dataProvider' => new ActiveDataProvider([
                'query' => $model->rosterQuery($rosterTournamentId), 'pagination' => ['pageSize' => 20],
                'sort' => ['defaultOrder' => ['name' => SORT_ASC], 'attributes' => ['name', 'position']],
            ]),
        ]);
    }
}
