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
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class PlayerController extends Controller {
    public function actionIndex() {
        $searchModel = new PlayerSearch();
        return $this->render('index', ['searchModel' => $searchModel, 'teams' => Team::options(),
            'dataProvider' => $searchModel->search(Yii::$app->request->queryParams)]);
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
