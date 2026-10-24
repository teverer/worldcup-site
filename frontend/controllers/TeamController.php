<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
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
        return $this->render('index', ['searchModel' => $searchModel,
            'dataProvider' => $searchModel->search(Yii::$app->request->queryParams)]);
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
        if ($tournamentId !== null && $tournamentId !== ''
            && (!is_scalar($tournamentId) || !ctype_digit((string) $tournamentId)
                || !in_array((int) $tournamentId, array_map('intval', array_column($tournaments, 'id')), true))) {
            throw new NotFoundHttpException('该球队没有此届参赛名单。');
        }
        return $this->render('view', ['model' => $model, 'tournaments' => $tournaments,
            'tournamentId' => $tournamentId, 'dataProvider' => new ActiveDataProvider([
                'query' => $model->rosterQuery($tournamentId), 'pagination' => ['pageSize' => 20],
                'sort' => ['defaultOrder' => ['name' => SORT_ASC], 'attributes' => ['name', 'position']],
            ])]);
    }
}
