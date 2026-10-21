<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 新建和编辑页面。
 * @var yii\web\View $this
 * @var yii\db\ActiveRecord $model
 */
use common\models\Team;
use yii\helpers\Html;
$label = $model instanceof Team ? '球队' : '球员';
$this->title = $model->isNewRecord ? '新增' . $label : '编辑：' . $model->name;
$this->params['breadcrumbs'][] = ['label' => $label . '管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<?= $this->render('_form', ['model' => $model]) ?>
