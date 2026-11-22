<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 上传作业文件。
 *
 * @var yii\web\View $this
 * @var common\models\Work $model
 */

$this->title = '上传作业文件';
$this->params['breadcrumbs'][] = ['label' => '作业管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= yii\helpers\Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
