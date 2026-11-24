<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:届次与比赛 —— 旧纪录页地址的 301
 *
 * 纪录页已并入届次页「统计」标签的历史坐标三块；
 * /record 保留 301，旧链接落到最新一届的 #history。
 */

namespace frontend\controllers;

use common\models\Tournament;
use yii\web\Controller;

class RecordController extends Controller
{
    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        $latest = Tournament::find()->orderBy(['year' => SORT_DESC])->one();

        if ($latest === null) {
            return $this->redirect(['/site/index'], 301);
        }

        return $this->redirect([
            '/tournament/view',
            'id' => $latest->id,
            '#' => 'history',
        ], 301);
    }
}
