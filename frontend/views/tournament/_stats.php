<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次详情页数据区
 *
 * @var yii\web\View $this
 * @var array $stats
 * @var array|null $third_place
 * @var common\models\Tournament $model
 */
use yii\helpers\Html;

$topScorer = $stats['top_scorer'];
$thirdPlaceName = $third_place !== null ? (string) $third_place['name'] : null;
?>
<section class="td-section td-section--data" id="charts" tabindex="-1"
         aria-labelledby="td-data-title">
    <header class="td-section-head">
        <h2 class="section-title" id="td-data-title">数据 <small>积分榜 · 射手榜 · 进球分布</small></h2>
        <p class="td-section-note">图表只读取已结束比赛的进球与积分,点球大战进球单列统计。</p>
    </header>

    <dl class="td-data-facts">
        <div class="td-data-fact">
            <dt>场均进球</dt>
            <dd><?= Html::encode(number_format((float) $stats['avg_goals'], 2)) ?></dd>
        </div>
        <div class="td-data-fact">
            <dt>季军</dt>
            <dd><?= Html::encode($thirdPlaceName !== null ? $thirdPlaceName : '待定') ?></dd>
        </div>
        <div class="td-data-fact td-data-fact--scorer">
            <dt>最佳射手</dt>
            <dd>
                <?php if ($topScorer !== null): ?>
                    <?= Html::encode((string) $topScorer['name']) ?>
                    <span class="td-data-fact-note">
                        <?= Html::encode((string) $topScorer['team']) ?> ·
                        <?= Html::encode((string) $topScorer['goals']) ?> 球
                    </span>
                <?php else: ?>
                    待定
                <?php endif ?>
            </dd>
        </div>
    </dl>

    <?= $this->render('@frontend/views/chart/_charts', ['tournamentId' => $model->id]) ?>
</section>
