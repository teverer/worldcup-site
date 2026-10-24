<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * 模块:球队与球员 —— 球员资料、参赛经历及分届统计。
 * @var yii\web\View $this
 * @var common\models\Player $model
 * @var array $participations
 * @var array $statistics
 */
use yii\helpers\Html;
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '球员目录', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
?>
<article class="catalog-page">
    <header class="catalog-heading">
        <?php if ($model->photo): ?><?= Html::img('@web/' . $model->photo, ['alt' => $model->name . '照片', 'class' => 'catalog-portrait']) ?><?php endif ?>
        <h1><?= Html::encode($model->name) ?></h1>
        <p><?= Html::encode(implode(' · ', array_filter([$model->nationality, $model->position, $model->birth_date]))) ?></p>
    </header>
    <dl class="catalog-statistics">
        <div><dt>世界杯进球</dt><dd><?= (int) array_sum(array_column($statistics, 'goals')) ?></dd></div>
        <div><dt>世界杯助攻</dt><dd><?= (int) array_sum(array_column($statistics, 'assists')) ?></dd></div>
        <div><dt>参赛届数</dt><dd><?= count($participations) ?></dd></div>
    </dl>
    <p class="text-muted">统计以已录入且已结束的比赛为准，不含乌龙球与点球大战。</p>
    <h2>球员介绍</h2>
    <p class="catalog-intro"><?= $model->intro ? nl2br(Html::encode($model->intro)) : '暂无球员介绍。' ?></p>
    <h2>世界杯参赛经历</h2>
    <?php if (!$participations): ?><p>尚未录入参赛经历。</p><?php else: ?>
    <div class="table-responsive"><table class="table catalog-table">
        <caption class="sr-only">球员历届参赛球队、号码和位置</caption>
        <thead><tr><th scope="col">届次</th><th scope="col">代表球队</th><th scope="col">球衣号</th><th scope="col">位置</th></tr></thead>
        <tbody><?php foreach ($participations as $row): ?><tr>
            <td><?= Html::encode($row['name']) ?></td>
            <td><?= Html::a(Html::encode($row['team_name']), ['/team/view', 'id' => $row['team_id']]) ?></td>
            <td><?= Html::encode($row['shirt_number'] ?? '—') ?></td>
            <td><?= Html::encode($row['position'] ?: ($model->position ?: '—')) ?></td>
        </tr><?php endforeach ?></tbody>
    </table></div><?php endif ?>
    <h2>各届进球与助攻</h2>
    <?php if (!$statistics): ?><p>暂无进球或助攻记录。</p><?php else: ?>
    <div class="table-responsive"><table class="table catalog-table">
        <caption class="sr-only">球员各届进球与助攻统计</caption>
        <thead><tr><th scope="col">年份</th><th scope="col">进球</th><th scope="col">助攻</th></tr></thead>
        <tbody><?php foreach ($statistics as $row): ?><tr>
            <td><?= (int) $row['year'] ?></td><td><?= (int) $row['goals'] ?></td><td><?= (int) $row['assists'] ?></td>
        </tr><?php endforeach ?></tbody>
    </table></div><?php endif ?>
</article>
