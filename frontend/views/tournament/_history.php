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
?>
<nav class="td-history" aria-label="届次翻页">
    <?php if ($previous !== null): ?>
        <a class="td-history-link td-history-link--prev"
           href="<?= Html::encode(Url::to(['view', 'id' => (int) $previous['id']])) ?>">
            <span class="td-history-dir">上一届</span>
            <span class="td-history-year"><?= Html::encode((string) $previous['year']) ?></span>
            <span class="td-history-name"><?= Html::encode((string) $previous['name']) ?></span>
        </a>
    <?php endif ?>

    <?php if ($next !== null): ?>
        <a class="td-history-link td-history-link--next"
           href="<?= Html::encode(Url::to(['view', 'id' => (int) $next['id']])) ?>">
            <span class="td-history-dir">下一届</span>
            <span class="td-history-year"><?= Html::encode((string) $next['year']) ?></span>
            <span class="td-history-name"><?= Html::encode((string) $next['name']) ?></span>
        </a>
    <?php endif ?>
</nav>
