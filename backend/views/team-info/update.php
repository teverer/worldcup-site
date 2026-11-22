<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 编辑团队介绍。
 *
 * @var yii\web\View $this
 * @var common\models\TeamInfo $model
 */

$this->title = '编辑团队信息';
$this->params['breadcrumbs'][] = ['label' => '团队信息', 'url' => ['index']];
$this->params['breadcrumbs'][] = '编辑';
?>
<h1><?= yii\helpers\Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
