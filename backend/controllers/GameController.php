<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台比赛增删改查与进球入口。
 */

namespace backend\controllers;

use Yii;
use backend\components\CatalogController;
use common\models\Game;
use common\models\GameSearch;
use yii\db\IntegrityException;

class GameController extends CatalogController
{
    /**
     * 通过基类已有属性注入模型与搜索类,避免重复声明无下划线前缀的受保护属性。
     */
    public function init()
    {
        parent::init();
        $this->modelClass = Game::class;
        $this->searchClass = GameSearch::class;
    }

    /**
     * 比赛详情同时展示本场进球,并提供“录入进球”入口。
     *
     * @param mixed $id
     * @return string
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $goals = $model->getGoals()
            ->with(['team', 'player', 'assistPlayer'])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'goals' => $goals,
        ]);
    }

    /**
     * 删除比赛。进球记录由数据库外键级联删除,页面上需二次确认。
     *
     * @param mixed $id
     * @return \yii\web\Response
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        try {
            if ($model->delete() === false) {
                Yii::$app->session->setFlash('error', implode('；', $model->getFirstErrors()));
            } else {
                Yii::$app->session->setFlash('success', '比赛及其进球记录已删除。');
            }
        } catch (IntegrityException $exception) {
            Yii::$app->session->setFlash('error', '比赛删除失败:仍有其他记录引用本场比赛。');
        }
        return $this->redirect(['index']);
    }

    /**
     * {@inheritdoc}
     */
    protected function edit($model, $view)
    {
        if ($model->load(Yii::$app->request->post())) {
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '比赛资料已保存。');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                $model->addError('tournament_id', '比赛与现有数据冲突,请检查届次、主客队和比分。');
            }
        }
        return $this->render($view, ['model' => $model]);
    }
}
