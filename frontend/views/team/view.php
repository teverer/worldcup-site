<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261108
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员、留言与资讯 —— 球队详情:身份头、参赛届次、名单与留言。
 *
 * @var yii\web\View $this
 * @var common\models\Team $model
 * @var array $tournaments
 * @var string|null $tournamentId
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use common\components\CommentTarget;
use frontend\widgets\CommentSection;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => '球队', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');

$currentTournament = null;
foreach ($tournaments as $tournament) {
    if ((string) $tournament['id'] === (string) $tournamentId) {
        $currentTournament = $tournament;
        break;
    }
}
$rosterTotal = (int) $dataProvider->totalCount;
?>
<article class="catalog-page team-detail">
    <header class="td-head">
        <div class="td-head-text">
            <h1><?= Html::encode($model->name) ?></h1>
            <p class="td-meta">
                <?= Html::encode($model->continent ?: '大洲待补') ?>
                <?php if ($model->code): ?> · <?= Html::encode($model->code) ?><?php endif ?>
                <?php if ($tournaments): ?>
                    · 参赛 <?= count($tournaments) ?> 届，最近 <?= Html::encode((string) $tournaments[0]['year']) ?>
                <?php endif ?>
            </p>
        </div>
        <div class="td-actions">
            <?= Html::a('看这队比赛', ['/game/index', 'team' => $model->name], ['class' => 'btn btn-primary']) ?>
        </div>
    </header>

    <?php if ($model->intro): ?>
        <p class="td-intro"><?= nl2br(Html::encode($model->intro)) ?></p>
    <?php endif ?>

    <section class="td-section" aria-labelledby="td-roster-title">
        <div class="td-section-head">
            <div>
                <h2 id="td-roster-title">参赛球员</h2>
                <p>
                    <?= $currentTournament
                        ? Html::encode($currentTournament['name']) . ' 的名单，共 ' . $rosterTotal . ' 人。'
                        : '所有届次去重后共 ' . $rosterTotal . ' 人；点年份只看那一届。' ?>
                </p>
            </div>
            <?php if (!$tournaments): ?>
                <span class="badge badge-muted">暂无参赛记录</span>
            <?php endif ?>
        </div>

        <?php if ($tournaments): ?>
            <nav class="td-editions" aria-label="按届次筛选名单">
                <?= Html::a('全部届次', ['/team/view', 'id' => $model->id], [
                    'class' => 'td-edition' . ($tournamentId === null || $tournamentId === '' ? ' is-active' : ''),
                ]) ?>
                <?php foreach ($tournaments as $tournament): ?>
                    <?= Html::a(Html::encode((string) $tournament['year']), [
                        '/team/view', 'id' => $model->id, 'tournamentId' => $tournament['id'],
                    ], [
                        'class' => 'td-edition' . ((string) $tournament['id'] === (string) $tournamentId ? ' is-active' : ''),
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
                                <td><?= Html::a(Html::encode($player->name), ['/player/view', 'id' => $player->id], ['class' => 'td-player']) ?></td>
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
</article>

<?= CommentSection::widget([
    'targetType' => CommentTarget::TEAM,
    'targetId' => $model->id,
]) ?>
