<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 作业文件详情。
 *
 * @var yii\web\View $this
 * @var common\models\Work $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => '作业管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$size = $model->fileSize();
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑或替换文件', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => ['method' => 'post', 'confirm' => '确定删除这个作业文件吗？'],
    ]) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>
<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'title',
        'type',
        'description',
        'file_path',
        [
            'label' => '文件大小',
            'value' => $size !== null ? Yii::$app->formatter->asShortSize($size, 1) : '文件不存在',
        ],
        'create_time:datetime',
    ],
]) ?>
