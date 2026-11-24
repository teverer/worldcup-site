<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 球员详情:身份头、生涯统计、参赛经历。
 *
 * @var yii\web\View $this
 * @var common\models\Player $model
 * @var array $participations
 * @var array $statistics
 */

use common\models\Player;
use yii\helpers\Html;

$this->title = $model->displayName();
$this->params['breadcrumbs'][] = ['label' => '球员', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');

$totalGoals = (int) array_sum(array_column($statistics, 'goals'));
$totalAssists = (int) array_sum(array_column($statistics, 'assists'));
$latestTeam = $participations ? $participations[0] : null;

// 参赛经历与各届进球/助攻按届次合成一张生涯表,避免两张表都要读一遍。
$career = [];
foreach ($participations as $row) {
    $year = (int) $row['year'];
    $career[$year] = [
        'year' => $year,
        'tournament_id' => $row['tournament_id'],
        'tournament_name' => $row['name'],
        'team_id' => $row['team_id'],
        'team_name' => $row['team_name'],
        'shirt' => $row['shirt_number'],
        'position' => $row['position'] ?: $model->position,
        'goals' => 0,
        'assists' => 0,
    ];
}
foreach ($statistics as $row) {
    $year = (int) $row['year'];
    if (!isset($career[$year])) {
        $career[$year] = [
            'year' => $year,
            'tournament_id' => null,
            'tournament_name' => (string) $year,
            'team_id' => null,
            'team_name' => '—',
            'shirt' => null,
            'position' => $model->position,
            'goals' => 0,
            'assists' => 0,
        ];
    }
    $career[$year]['goals'] = (int) $row['goals'];
    $career[$year]['assists'] = (int) $row['assists'];
}
krsort($career);
$showingChineseName = $model->hasChineseName() && !Player::prefersOriginalName();
// 两种显示模式都保留另一种姓名,避免切到原名后认不出中文名。
$nameContext = null;
if ($model->hasChineseName()) {
    $nameContext = $showingChineseName
        ? '原名 ' . $model->name
        : '中文名 ' . $model->name_zh;
}
$metaParts = array_filter([
    $nameContext,
    $model->nationality,
    $model->position,
    $model->birth_date,
]);
$nameToggleUrl = [
    '/preference/name-lang',
    'lang' => $showingChineseName ? 'orig' : 'zh',
    'return' => Yii::$app->request->url,
];
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
                <?php if ($model->hasChineseName()): ?>
                    <?= Html::a(
                        $showingChineseName ? '显示原名' : '显示中文名',
                        $nameToggleUrl,
                        ['class' => 'pd-name-toggle']
                    ) ?>
                <?php endif ?>
            </div>
        </div>
        <div class="pd-actions">
            <?= $this->render('//partials/_name_lang') ?>
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

    <?= $this->render('_career_chart', ['statistics' => $statistics]) ?>

    <?php if ($model->intro): ?>
        <section class="pd-section" aria-labelledby="pd-intro-title">
            <h2 id="pd-intro-title">球员介绍</h2>
            <p class="catalog-intro"><?= nl2br(Html::encode($model->intro)) ?></p>
        </section>
    <?php endif ?>

    <section class="pd-section" aria-labelledby="pd-history-title">
        <div class="pd-section-head">
            <h2 id="pd-history-title">世界杯生涯</h2>
            <p>按届次倒序；进球与助攻只计已结束比赛，不含乌龙球与点球大战。</p>
        </div>
        <?php if (!$career): ?>
            <div class="empty-state">
                <h3>暂无参赛经历</h3>
                <p>该球员的世界杯参赛记录尚未录入；可以先从球队名单找到他。</p>
                <p><?= Html::a('按球队浏览', ['/team/index']) ?> · <?= Html::a('回到球员索引', ['/player/index']) ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table pd-table pd-table--history">
                    <caption class="sr-only">球员历届参赛球队、号码、位置、进球与助攻</caption>
                    <thead>
                        <tr>
                            <th scope="col">届次</th>
                            <th scope="col">代表球队</th>
                            <th scope="col">球衣号</th>
                            <th scope="col">位置</th>
                            <th scope="col">进球</th>
                            <th scope="col">助攻</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($career as $row): ?>
                            <tr>
                                <td>
                                    <?= $row['tournament_id']
                                        ? Html::a(Html::encode($row['tournament_name']), ['/tournament/view', 'id' => $row['tournament_id']])
                                        : Html::encode($row['tournament_name']) ?>
                                </td>
                                <td>
                                    <?= $row['team_id']
                                        ? Html::a(Html::encode($row['team_name']), ['/team/view', 'id' => $row['team_id']])
                                        : Html::encode($row['team_name']) ?>
                                </td>
                                <td><?= Html::encode($row['shirt'] ?? '—') ?></td>
                                <td><?= Html::encode($row['position'] ?: '—') ?></td>
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
