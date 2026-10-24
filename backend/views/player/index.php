<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 后台球员页面。
 * @var yii\web\View $this
 * @var yii\base\Model $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 */
echo $this->render('../catalog/_index', ['isTeam' => false, 'searchModel' => $searchModel, 'dataProvider' => $dataProvider, 'teams' => $teams]);
