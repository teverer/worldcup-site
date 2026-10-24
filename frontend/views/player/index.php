<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球员列表。
 * @var yii\web\View $this
 * @var common\models\PlayerSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */
echo $this->render('../catalog/_index', ['isTeam' => false, 'searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'teams' => $teams]);
