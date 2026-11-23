<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 公开球员目录、履历和统计。
 */

namespace frontend\controllers;

use Yii;
use common\models\Player;
use common\models\PlayerSearch;
use common\models\Team;
use frontend\models\PlayerIndex;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class PlayerController extends Controller {
    public function actionIndex() {
        $searchModel = new PlayerSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // 没有任何条件时,先给「每届最佳射手」这个可读的默认集合;
        // 访客点「浏览全部」或翻页后才显示 4282 人的全量列表。
        $hasFilter = trim((string) $searchModel->q) !== ''
            || ($searchModel->teamId !== null && $searchModel->teamId !== '')
            || trim((string) $searchModel->position) !== '';
        $showAll = (string) Yii::$app->request->get('all') === '1';

        return $this->render('index', [
            'searchModel' => $searchModel,
            'teams' => Team::options(),
            'positions' => ['门将', '后卫', '中场', '前锋'],
            'dataProvider' => $dataProvider,
            'spotlight' => !$hasFilter && !$showAll ? PlayerIndex::topScorersByTournament() : [],
            'totalPlayers' => (int) Player::find()->count(),
        ]);
    }

    public function actionView($id) {
        if (!is_scalar($id) || !ctype_digit((string) $id) || (int) $id < 1) {
            throw new NotFoundHttpException('球员不存在。');
        }
        $model = Player::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('球员不存在。');
        }
        return $this->render('view', ['model' => $model, 'participations' => $model->participations(),
            'statistics' => $model->statistics()]);
    }
}
