<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 成员详情。
 *
 * @var yii\web\View $this
 * @var common\models\Member $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '成员管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => ['method' => 'post', 'confirm' => '确定删除这名成员吗？'],
    ]) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>
<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'name',
        'student_id',
        'role',
        'duty',
        [
            'attribute' => 'avatar',
            'format' => 'raw',
            'value' => $model->avatar
                ? Html::tag('code', Html::encode($model->avatar))
                : '<span class="not-set">未上传</span>',
        ],
        'intro:ntext',
        'create_time:datetime',
    ],
]) ?>
