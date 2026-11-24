<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 球队详情:身份头、参赛届次、名单。
 *
 * @var yii\web\View $this
 * @var common\models\Team $model
 * @var array $tournaments
 * @var string|int|null $tournamentId
 * @var int|null $rosterTournamentId
 * @var bool $allRoster
 * @var array $records 历届战绩
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '球队', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');

$currentTournament = null;
foreach ($tournaments as $tournament) {
    if ((string) $tournament['id'] === (string) $rosterTournamentId) {
        $currentTournament = $tournament;
        break;
    }
}
$rosterTotal = (int) $dataProvider->totalCount;

// 队史总计：从历届战绩直接汇总，不额外查库。
$totalPlayed = 0;
$totalWon = 0;
$totalDrawn = 0;
$totalLost = 0;
$totalGoalsFor = 0;
$totalGoalsAgainst = 0;
foreach ($records as $record) {
    $totalPlayed += (int) $record['played'];
    $totalWon += (int) $record['won'];
    $totalDrawn += (int) $record['drawn'];
    $totalLost += (int) $record['lost'];
    $totalGoalsFor += (int) $record['goals_for'];
    $totalGoalsAgainst += (int) $record['goals_against'];
}
?>
<article class="catalog-page team-detail">
    <header class="td-head">
        <div class="td-head-text">
            <h1><?= Html::encode($model->name) ?></h1>
            <p class="td-meta">
                <?= Html::encode($model->continent ?: '大洲待补') ?>
                <?php if ($model->code): ?> · <?= Html::encode($model->code) ?><?php endif ?>
                <?php if ($tournaments): ?>
                    · 最近 <?= Html::encode((string) $tournaments[0]['year']) ?>
                <?php endif ?>
            </p>

            <?php // 队史总计从右栏收回身份头:一眼看到,不再让右侧空出一整列。 ?>
            <dl class="td-head-facts" aria-label="队史总计">
                <div>
                    <dt>参赛</dt>
                    <dd><?= count($tournaments) ?><span>届</span></dd>
                </div>
                <div>
                    <dt>场次</dt>
                    <dd><?= $totalPlayed ?></dd>
                </div>
                <div>
                    <dt>胜-平-负</dt>
                    <dd><?= $totalWon ?>-<?= $totalDrawn ?>-<?= $totalLost ?></dd>
                </div>
                <div>
                    <dt>进 / 失</dt>
                    <dd><?= $totalGoalsFor ?> / <?= $totalGoalsAgainst ?></dd>
                </div>
            </dl>
        </div>
        <div class="td-actions">
            <?= Html::a('看这队比赛', ['/game/index', 'team' => $model->name], ['class' => 'btn btn-primary']) ?>
        </div>
    </header>

    <?php if ($model->intro): ?>
        <p class="td-intro"><?= nl2br(Html::encode($model->intro)) ?></p>
    <?php endif ?>

    <?= $this->render('_history_chart', ['records' => $records]) ?>

    <div class="td-body">
        <div class="td-body-main">
    <section class="td-section" aria-labelledby="td-records-title">
        <div class="td-section-head">
            <div>
                <h2 id="td-records-title">历届战绩</h2>
                <p>只计已结束比赛；进球按受益球队计入，乌龙球算给对方；点年份进入那一届的晋级图与统计。</p>
            </div>
        </div>

        <?php if (!$records): ?>
            <div class="empty-state td-empty">
                <h3>还没有比赛记录</h3>
                <p>这支队的世界杯比赛尚未录入；可以先去比赛索引按队名查。</p>
                <p><?= Html::a('看这队比赛', ['/game/index', 'team' => $model->name]) ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table catalog-table td-records">
                    <caption class="sr-only"><?= Html::encode($model->name) ?> 历届世界杯战绩</caption>
                    <thead>
                        <tr>
                            <th scope="col">届次</th>
                            <th scope="col">最终阶段</th>
                            <th scope="col">场</th>
                            <th scope="col">胜-平-负</th>
                            <th scope="col">进 / 失</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                            <tr>
                                <td><?= Html::a(Html::encode((string) $record['year']), [
                                    '/tournament/view', 'id' => $record['tournament_id'],
                                ]) ?></td>
                                <td><?= Html::encode($record['stage']) ?></td>
                                <td><?= (int) $record['played'] ?></td>
                                <td><?= (int) $record['won'] ?>-<?= (int) $record['drawn'] ?>-<?= (int) $record['lost'] ?></td>
                                <td><?= (int) $record['goals_for'] ?> / <?= (int) $record['goals_against'] ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <section class="td-section" aria-labelledby="td-roster-title">
        <div class="td-section-head">
            <div>
                <h2 id="td-roster-title">参赛球员</h2>
                <p>
                    <?= $currentTournament && !$allRoster
                        ? Html::encode($currentTournament['name']) . '的名单，共 ' . $rosterTotal . ' 人。'
                        : '所有届次去重后共 ' . $rosterTotal . ' 人；点年份只看那一届。' ?>
                </p>
            </div>
            <?php if (!$tournaments): ?>
                <span class="badge badge-muted">暂无参赛记录</span>
            <?php else: ?>
                <?= $this->render('//partials/_name_lang') ?>
            <?php endif ?>
        </div>

        <?php if ($tournaments): ?>
            <nav class="td-editions" aria-label="按届次筛选名单">
                <?= Html::a('全部届次', ['/team/view', 'id' => $model->id, 'tournamentId' => 'all'], [
                    'class' => 'td-edition' . ($allRoster ? ' is-active' : ''),
                ]) ?>
                <?php foreach ($tournaments as $tournament): ?>
                    <?= Html::a(Html::encode((string) $tournament['year']), [
                        '/team/view', 'id' => $model->id, 'tournamentId' => $tournament['id'],
                    ], [
                        'class' => 'td-edition' . (!$allRoster && (string) $tournament['id'] === (string) $rosterTournamentId ? ' is-active' : ''),
                    ]) ?>
                <?php endforeach ?>
            </nav>
        <?php endif ?>

        <?php if ($rosterTotal === 0): ?>
            <div class="empty-state td-empty">
                <h3><?= $currentTournament ? '这一届还没有录入名单' : '还没有录入参赛球员' ?></h3>
                <p><?= $currentTournament ? '换一个年份，或者看这队在本届的比赛。' : '名单按届次归档，录入后会出现在这里。' ?></p>
                <p><?= Html::a('看这队比赛', ['/game/index', 'team' => $model->name]) ?></p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table td-table">
                    <caption class="sr-only"><?= Html::encode($model->name) ?> 世界杯参赛球员名单</caption>
                    <thead>
                        <tr>
                            <th scope="col">球员</th>
                            <th scope="col">位置</th>
                            <th scope="col">出生年</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dataProvider->getModels() as $player): ?>
                            <tr>
                                <td><?= Html::a(Html::encode($player->displayName()), ['/player/view', 'id' => $player->id], [
                                    'class' => 'td-player',
                                    'title' => $player->name,
                                ]) ?></td>
                                <td><?= Html::encode($player->position ?: '待补') ?></td>
                                <td><?= Html::encode($player->birth_date ? substr($player->birth_date, 0, 4) : '—') ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'prevPageLabel' => '上一页',
                'nextPageLabel' => '下一页',
            ]) ?>
        <?php endif ?>
    </section>
        </div>

    </div>
</article>
