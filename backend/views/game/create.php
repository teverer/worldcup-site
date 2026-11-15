<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 新增比赛页面。
 *
 * @var yii\web\View $this
 * @var common\models\Game $model
 */

use yii\helpers\Html;

$this->title = '新增比赛';
$this->params['breadcrumbs'][] = ['label' => '比赛管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
