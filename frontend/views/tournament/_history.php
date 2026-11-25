<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261118
 * 模块:届次与比赛 —— 届次详情页上一届 / 下一届导航
 *
 * @var yii\web\View $this
 * @var array|null $previous
 * @var array|null $next
 */
use yii\helpers\Html;
use yii\helpers\Url;

if ($previous === null && $next === null) {
    return;
}

// 首尾两届只有一侧有邻届；空出来的一格给「回到首页」，
// 既平衡版式，也避免页面在最后一届之后变成死路。
$wallUrl = Url::to(['/site/index']);

// 年份已经在前面单独放大，名称里再带一次「2018年」就是重复。
$editionName = static function ($name) {
    $stripped = preg_replace('/^\d{4}年/u', '', (string) $name);
    return $stripped !== null && $stripped !== '' ? $stripped : (string) $name;
};
?>
<nav class="td-history" aria-label="届次翻页">
    <?php if ($previous !== null): ?>
        <a class="td-history-link td-history-link--prev"
           href="<?= Html::encode(Url::to(['view', 'id' => (int) $previous['id']])) ?>">
            <span class="td-history-dir">上一届</span>
            <span class="td-history-year"><?= Html::encode((string) $previous['year']) ?></span>
            <span class="td-history-name"><?= Html::encode($editionName($previous['name'])) ?></span>
        </a>
    <?php else: ?>
        <a class="td-history-link td-history-link--all" href="<?= Html::encode($wallUrl) ?>">
            <span class="td-history-dir">全部届次</span>
            <span class="td-history-year">1986—2026</span>
            <span class="td-history-name">回到首页</span>
        </a>
    <?php endif ?>

    <?php if ($next !== null): ?>
        <a class="td-history-link td-history-link--next"
           href="<?= Html::encode(Url::to(['view', 'id' => (int) $next['id']])) ?>">
            <span class="td-history-dir">下一届</span>
            <span class="td-history-year"><?= Html::encode((string) $next['year']) ?></span>
            <span class="td-history-name"><?= Html::encode($editionName($next['name'])) ?></span>
        </a>
    <?php else: ?>
        <a class="td-history-link td-history-link--next td-history-link--all"
           href="<?= Html::encode($wallUrl) ?>">
            <span class="td-history-dir">全部届次</span>
            <span class="td-history-year">1986—2026</span>
            <span class="td-history-name">回到首页</span>
        </a>
    <?php endif ?>
</nav>
