<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 编辑成员。
 *
 * @var yii\web\View $this
 * @var common\models\Member $model
 */

$this->title = '编辑成员：' . $model->name;
$this->params['breadcrumbs'][] = ['label' => '成员管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = '编辑';
?>
<h1><?= yii\helpers\Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
