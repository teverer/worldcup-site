<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 后台届次增删改查。
 */

namespace backend\controllers;

use Yii;
use backend\components\CatalogController;
use common\models\Tournament;
use common\models\TournamentSearch;
use yii\db\IntegrityException;

class TournamentController extends CatalogController
{
    /**
     * 通过基类已有属性注入模型与搜索类,避免重复声明无下划线前缀的受保护属性。
     */
    public function init()
    {
        parent::init();
        $this->modelClass = Tournament::class;
        $this->searchClass = TournamentSearch::class;
    }

    /**
     * 删除届次。数据库外键会级联删除参赛队、名单、比赛和进球记录,
     * 页面上必须先用 data-confirm 做二次确认。
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
                Yii::$app->session->setFlash('success', '届次及其参赛队、名单、比赛和进球记录已删除。');
            }
        } catch (IntegrityException $exception) {
            Yii::$app->session->setFlash('error', '届次删除失败:仍有其他记录引用本届数据。');
        }
        return $this->redirect(['index']);
    }
}
