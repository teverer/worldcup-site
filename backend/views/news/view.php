<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261112
 * 模块:留言与资讯 —— 后台资讯详情。
 *
 * @var yii\web\View $this
 * @var common\models\News $model
 */

use yii\helpers\Html;
use yii\helpers\HtmlPurifier;
use yii\widgets\DetailView;

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => '资讯管理', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$safeContent = HtmlPurifier::process($model->content ?: '', ['Cache.DefinitionImpl' => null]);
?>
<h1><?= Html::encode($this->title) ?></h1>
<p>
    <?= Html::a('编辑', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    <?= Html::a('删除', ['delete', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'confirm' => '确定删除这条资讯及其全部留言吗？',
            'method' => 'post',
        ],
    ]) ?>
    <?= Html::a('返回列表', ['index'], ['class' => 'btn btn-default']) ?>
</p>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'title',
        'author',
        [
            'attribute' => 'status',
            'value' => common\models\News::statusOptions()[$model->status],
        ],
        'publish_time:datetime',
        'create_time:datetime',
        [
            'attribute' => 'cover',
            'format' => 'raw',
            'value' => $model->cover
                ? Html::tag('code', Html::encode($model->cover))
                : '<span class="not-set">（未上传）</span>',
        ],
        [
            'attribute' => 'content',
            'format' => 'raw',
            'value' => $safeContent !== '' ? $safeContent : '<span class="not-set">（无正文）</span>',
        ],
    ],
]) ?>
