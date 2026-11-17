<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球队列表。
 * @var yii\web\View $this
 * @var common\models\TeamSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */
echo $this->render('../catalog/_index', ['isTeam' => true, 'searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'teams' => []]);
