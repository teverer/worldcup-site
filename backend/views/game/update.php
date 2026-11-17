<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 编辑比赛页面。
 *
 * @var yii\web\View $this
 * @var common\models\Game $model
 */

use yii\helpers\Html;

$this->title = '编辑比赛:' . $model->matchTitle;
$this->params['breadcrumbs'][] = ['label' => '比赛管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->matchTitle, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = '编辑';
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
