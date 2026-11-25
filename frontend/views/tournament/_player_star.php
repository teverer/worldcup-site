<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:届次与比赛 —— 四十年球员点云
 *
 * 「历届数据」章的最后一块：每个点是一个人，横轴生涯进球、纵轴参赛届数（可切助攻），按届播放。
 * 数据懒加载：本块进入视口后才注入 D3 并请求 JSON；不替换上面两张本届图。
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$starCssVersion = @filemtime(Yii::getAlias('@webroot/css/player-star.css')) ?: '';
$this->registerCssFile(Url::to('@web/css/player-star.css') . ($starCssVersion !== '' ? '?v=' . $starCssVersion : ''));
$starJsVersion = @filemtime(Yii::getAlias('@webroot/js/player-star.js')) ?: '';
$this->registerJsFile(
    Url::to('@web/js/player-star.js') . ($starJsVersion !== '' ? '?v=' . $starJsVersion : ''),
    [
        'position' => View::POS_END,
        'id' => 'player-star-js',
    ]
);
?>
<section class="player-star" id="player-star" aria-labelledby="player-star-title"
         data-star-url="<?= Html::encode(Url::to(['/player-star/data'])) ?>"
         data-d3-src="<?= Html::encode(Url::to('@web/js/vendor/d3.v7.min.js')) ?>"
         data-player-url="<?= Html::encode(Url::to(['/player/view'])) ?>">
    <header class="player-star-head">
        <div class="player-star-head-row">
            <h2 id="player-star-title">球员星云</h2>
        </div>
    </header>

    <div class="player-star-controls" role="group" aria-label="四十年球员分布播放控制">
        <button type="button" class="player-star-play" aria-pressed="false">播放四十年</button>
        <label class="player-star-year-wrap" for="player-star-year">
            <output class="player-star-year" for="player-star-year">2026</output>
            <input type="range" id="player-star-year" min="1986" max="2026" step="4" value="2026" aria-label="年份">
        </label>
        <div class="player-star-mode" role="group" aria-label="纵轴">
            <button type="button" data-y="editions" aria-pressed="true" title="纵轴：参赛届数">参赛届数</button>
            <button type="button" data-y="assists" aria-pressed="false" title="纵轴：生涯助攻">生涯助攻</button>
        </div>
    </div>

    <div class="player-star-layout">
        <div class="player-star-stage" role="img"
             aria-label="四十年球员分布：横轴生涯进球，纵轴参赛届数；按年份播放，点球员进生涯页。">
            <span class="player-star-ghost-year" aria-hidden="true" data-star-ghost-year>2026</span>
            <p class="player-star-loading">点云加载中…</p>
            <div class="player-star-tip" role="status" aria-live="polite"></div>
        </div>
    </div>

    <div class="player-star-foot">
        <p class="player-star-legend">
            <span><i class="ps-dot ps-eu"></i>欧洲</span>
            <span><i class="ps-dot ps-sa"></i>南美</span>
            <span><i class="ps-dot ps-af"></i>非洲</span>
            <span><i class="ps-dot ps-as"></i>亚洲</span>
            <span><i class="ps-dot ps-na"></i>中北美</span>
            <span><i class="ps-dot ps-other"></i>其他</span>
            <span><i class="ps-lit" aria-hidden="true"></i>亮 = 该届进球</span>
        </p>
    </div>

    <p class="player-star-note">
        图只画了进球 ≥2 的球员。
    </p>

    <noscript>
        <p class="player-star-empty">球员点云需要启用 JavaScript；可先到
            <?= Html::a('球员索引', ['/player/index']) ?> 浏览全部球员。</p>
    </noscript>
</section>
