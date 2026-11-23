<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * 模块:框架与团队主页 —— 后台团队信息概览。
 *
 * @var yii\web\View $this
 * @var common\models\TeamInfo $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = '团队信息';
$this->params['breadcrumbs'][] = '团队管理';
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑团队信息', ['update'], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('成员管理', ['/member/index'], ['class' => 'btn btn-default']) ?>
    <?= Html::a('作业管理', ['/work/index'], ['class' => 'btn btn-default']) ?>
</p>

<?php if ($model->isNewRecord): ?>
    <div class="alert alert-info">团队介绍尚未创建，点击“编辑团队信息”补全。</div>
<?php else: ?>
    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'slogan',
            [
                'attribute' => 'logo',
                'format' => 'raw',
                'value' => $model->logo
                    ? Html::tag('code', Html::encode($model->logo))
                    : '<span class="not-set">未上传</span>',
            ],
            'intro:ntext',
            'create_time:datetime',
        ],
    ]) ?>
<?php endif ?>
