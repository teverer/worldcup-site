<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261115
 * 模块:届次与比赛 —— 历届世界杯卡片列表。
 *
 * @var yii\web\View $this
 * @var common\models\Tournament[] $tournaments
 */
use yii\helpers\Html;

$this->title = '世界杯届次';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');
$this->registerCssFile('@web/css/game.css');
?>
<section class="tournament-page">
    <header class="tournament-heading">
        <p class="tournament-kicker">世界杯数据档案</p>
        <h1>历届世界杯</h1>
        <p>从 1986 到 2026，浏览每一届的赛制、参赛队与完整赛程。</p>
    </header>

    <?php if (!$tournaments): ?>
        <p class="text-muted">暂无届次数据。</p>
    <?php else: ?>
        <div class="tournament-grid">
            <?php foreach ($tournaments as $tournament): ?>
                <?php
                $dateParts = array_filter([$tournament->start_date, $tournament->end_date],
                    static function ($date) {
                        return $date !== null && $date !== '';
                    });
                $dateRange = $dateParts ? implode(' 至 ', $dateParts) : '待定';
                ?>
                <article class="tournament-card">
                    <p class="tournament-year"><?= Html::encode($tournament->year) ?></p>
                    <h2>
                        <?= Html::a(Html::encode($tournament->name), ['view', 'id' => $tournament->id]) ?>
                    </h2>
                    <dl class="tournament-meta">
                        <dt>东道主</dt>
                        <dd><?= $tournament->host_country !== null && $tournament->host_country !== ''
                            ? Html::encode($tournament->host_country) : '待定' ?></dd>
                        <dt>参赛队</dt>
                        <dd><?= $tournament->team_count !== null
                            ? Html::encode($tournament->team_count) . ' 支' : '待定' ?></dd>
                        <dt>时间</dt>
                        <dd><?= Html::encode($dateRange) ?></dd>
                    </dl>
                    <p class="tournament-card-link">
                        <?= Html::a('查看届次详情', ['view', 'id' => $tournament->id], ['class' => 'btn btn-success btn-sm']) ?>
                    </p>
                </article>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</section>
