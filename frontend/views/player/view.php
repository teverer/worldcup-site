<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员、留言与资讯 —— 球员详情:身份头、生涯统计、参赛经历与留言。
 *
 * @var yii\web\View $this
 * @var common\models\Player $model
 * @var array $participations
 * @var array $statistics
 */

use common\components\CommentTarget;
use frontend\widgets\CommentSection;
use yii\helpers\Html;

$this->title = $model->displayName();
$this->params['breadcrumbs'][] = ['label' => '球员', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');

$totalGoals = (int) array_sum(array_column($statistics, 'goals'));
$totalAssists = (int) array_sum(array_column($statistics, 'assists'));
$latestTeam = $participations ? $participations[0] : null;
$metaParts = array_filter([
    $model->hasChineseName() ? '原名 ' . $model->name : null,
    $model->nationality,
    $model->position,
    $model->birth_date,
]);
?>
<article class="catalog-page player-detail">
    <header class="pd-head">
        <div class="pd-head-text">
            <?php if ($model->photo): ?>
                <?= Html::img('@web/' . $model->photo, ['alt' => $model->name . '照片', 'class' => 'pd-photo']) ?>
            <?php endif ?>
            <div>
                <h1><?= Html::encode($model->displayName()) ?></h1>
                <p class="pd-meta"><?= Html::encode($metaParts ? implode(' · ', $metaParts) : '资料待补') ?></p>
            </div>
        </div>
        <div class="pd-actions">
            <?php if ($latestTeam): ?>
                <?= Html::a('看' . $latestTeam['team_name'] . '队', ['/team/view', 'id' => $latestTeam['team_id']], ['class' => 'btn btn-primary']) ?>
            <?php else: ?>
                <?= Html::a('回到球员索引', ['/player/index'], ['class' => 'btn btn-primary']) ?>
            <?php endif ?>
        </div>
    </header>

    <dl class="pd-stats" aria-label="世界杯生涯统计">
        <div class="pd-stat">
            <dt>世界杯进球</dt>
            <dd><?= $totalGoals ?></dd>
        </div>
        <div class="pd-stat">
            <dt>世界杯助攻</dt>
            <dd><?= $totalAssists ?></dd>
        </div>
        <div class="pd-stat">
            <dt>参赛届数</dt>
            <dd><?= count($participations) ?></dd>
        </div>
    </dl>
    <p class="pd-note">统计以已录入且已结束的比赛为准，不含乌龙球与点球大战。</p>

    <?php if ($model->intro): ?>
        <section class="pd-section" aria-labelledby="pd-intro-title">
            <h2 id="pd-intro-title">球员介绍</h2>
            <p class="catalog-intro"><?= nl2br(Html::encode($model->intro)) ?></p>
        </section>
    <?php endif ?>

    <section class="pd-section" aria-labelledby="pd-history-title">
        <div class="pd-section-head">
            <h2 id="pd-history-title">世界杯参赛经历</h2>
        </div>
        <?php if (!$participations): ?>
            <div class="empty-state">
                <h3>暂无参赛经历</h3>
                <p>该球员的世界杯参赛记录尚未录入；可以先从球队名单找到他。</p>
                <p><?= Html::a('按球队浏览', ['/team/index']) ?> · <?= Html::a('回到球员索引', ['/player/index']) ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table pd-table pd-table--history">
                    <caption class="sr-only">球员历届参赛球队、号码和位置</caption>
                    <thead>
                        <tr>
                            <th scope="col">届次</th>
                            <th scope="col">代表球队</th>
                            <th scope="col">球衣号</th>
                            <th scope="col">位置</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participations as $row): ?>
                            <tr>
                                <td><?= Html::a(Html::encode($row['name']), ['/tournament/view', 'id' => $row['tournament_id']]) ?></td>
                                <td><?= Html::a(Html::encode($row['team_name']), ['/team/view', 'id' => $row['team_id']]) ?></td>
                                <td><?= Html::encode($row['shirt_number'] ?? '—') ?></td>
                                <td><?= Html::encode($row['position'] ?: ($model->position ?: '—')) ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <section class="pd-section" aria-labelledby="pd-stats-title">
        <div class="pd-section-head">
            <h2 id="pd-stats-title">各届进球与助攻</h2>
        </div>
        <?php if (!$statistics): ?>
            <div class="empty-state">
                <h3>暂无进球或助攻记录</h3>
                <p>统计以已结束比赛为准，不含乌龙球与点球大战。</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table pd-table pd-table--stats">
                    <caption class="sr-only">球员各届进球与助攻统计</caption>
                    <thead>
                        <tr>
                            <th scope="col">年份</th>
                            <th scope="col">进球</th>
                            <th scope="col">助攻</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statistics as $row): ?>
                            <tr>
                                <td><?= (int) $row['year'] ?></td>
                                <td><?= (int) $row['goals'] ?></td>
                                <td><?= (int) $row['assists'] ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>
</article>

<?= CommentSection::widget([
    'targetType' => CommentTarget::PLAYER,
    'targetId' => $model->id,
]) ?>
