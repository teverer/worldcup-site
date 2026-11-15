<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台参赛名单增删改查。
 */

namespace backend\controllers;

use Yii;
use backend\components\CatalogController;
use common\models\Squad;
use common\models\SquadSearch;
use yii\db\IntegrityException;

class SquadController extends CatalogController
{
    /**
     * 通过基类已有属性注入模型与搜索类,避免重复声明无下划线前缀的受保护属性。
     */
    public function init()
    {
        parent::init();
        $this->modelClass = Squad::class;
        $this->searchClass = SquadSearch::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function edit($model, $view)
    {
        if ($model->load(Yii::$app->request->post())) {
            try {
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', '名单记录已保存。');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (IntegrityException $exception) {
                $model->addError('tournament_id', '名单记录与现有数据冲突,请检查届次、球员和球衣号。');
            }
        }
        return $this->render($view, ['model' => $model]);
    }
}
