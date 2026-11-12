<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261112
 * 模块:留言与资讯 —— 后台编辑资讯页面。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

$this->title = '编辑：' . $model->title;
$this->params['breadcrumbs'][] = ['label' => '资讯管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = '编辑';
?>
<h1><?= yii\helpers\Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
