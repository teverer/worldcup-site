<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台进球记录增删改查。
 */

namespace backend\controllers;

use Yii;
use backend\components\CatalogController;
use common\models\Game;
use common\models\Goal;
use common\models\GoalSearch;
use yii\db\IntegrityException;

class GoalController extends CatalogController
{
    /**
     * 通过基类已有属性注入模型与搜索类,避免重复声明无下划线前缀的受保护属性。
     */
    public function init()
    {
        parent::init();
        $this->modelClass = Goal::class;
        $this->searchClass = GoalSearch::class;
    }

    /**
     * 从比赛详情页跳转录入进球时,用 game_id 预填比赛。
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Goal();
        $gameId = Yii::$app->request->get('game_id');
        if (is_scalar($gameId) && ctype_digit((string) $gameId)
            && Game::find()->where(['id' => (int) $gameId])->exists()) {
            $model->game_id = (int) $gameId;
        }
        return $this->edit($model, 'create');
    }

    /**
     * {@inheritdoc}
     */
    protected function edit($model, $view)
    {
        if ($model->load(Yii::$app->request->post())) {
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '进球记录已保存。');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                $model->addError('game_id', '进球记录与现有数据冲突,请检查比赛、球队和球员。');
            }
        }
        return $this->render($view, ['model' => $model]);
    }
}
