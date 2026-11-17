<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261112
 * 模块:留言与资讯 —— 后台新增资讯页面。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

$this->title = '新增资讯';
$this->params['breadcrumbs'][] = ['label' => '资讯管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= yii\helpers\Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
