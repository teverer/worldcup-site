<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 录入进球页面。
 *
 * @var yii\web\View $this
 * @var common\models\Goal $model
 */

use yii\helpers\Html;

$this->title = '录入进球';
$this->params['breadcrumbs'][] = ['label' => '进球管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
