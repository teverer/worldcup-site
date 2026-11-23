<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 球队索引:按大洲分组浏览,可按名称或代码查找。
 *
 * @var yii\web\View $this
 * @var common\models\TeamSearch $searchModel
 * @var array $groups 大洲 => Team[]
 * @var int $total
 * @var bool $hasFilter
 */

use common\models\Team;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = '球队';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');

$shown = 0;
foreach ($groups as $teams) {
    $shown += count($teams);
}
?>
<section class="catalog-page team-index">
    <header class="pl-head">
        <h1>球队</h1>
        <p><?= (int) $total ?> 支国家队，按大洲浏览；知道名字或代码，就在上方直接搜。</p>
    </header>

    <?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
    <form class="pl-search" method="get" action="<?= Html::encode(Url::to(['/team/index'])) ?>" role="search">
        <div class="pl-search-main">
            <label class="sr-only" for="team-q">球队名称或 FIFA 代码</label>
            <input class="form-control pl-input" type="search" id="team-q" name="q"
                   value="<?= Html::encode((string) $searchModel->q) ?>"
                   placeholder="输入球队名或代码，如 巴西、BRA" maxlength="100">
            <button class="btn btn-primary" type="submit">查找球队</button>
        </div>
        <div class="pl-search-filters">
            <label class="sr-only" for="team-continent">所属大洲</label>
            <select class="form-control" id="team-continent" name="continent">
                <option value="">全部大洲</option>
                <?php foreach (Team::CONTINENTS as $continent): ?>
                    <option value="<?= Html::encode($continent) ?>"<?= $searchModel->continent === $continent ? ' selected' : '' ?>>
                        <?= Html::encode($continent) ?>
                    </option>
                <?php endforeach ?>
            </select>
            <?php if ($hasFilter): ?>
                <?= Html::a('清除条件', ['/team/index'], ['class' => 'pl-clear']) ?>
            <?php endif ?>
        </div>
    </form>

    <?php if ($hasFilter): ?>
        <p class="ti-summary">找到 <?= (int) $shown ?> 支球队。</p>
    <?php endif ?>

    <?php if (!$groups): ?>
        <div class="empty-state pl-empty">
            <h3>没有找到球队</h3>
            <p>换一个名称或代码，或者清除大洲条件。</p>
            <p><?= Html::a('清除全部条件', ['/team/index']) ?> · <?= Html::a('按球员找', ['/player/index']) ?></p>
        </div>
    <?php else: ?>
        <?php $groupIndex = 0; foreach ($groups as $continent => $teams): $groupIndex++; ?>
            <section class="ti-group" aria-labelledby="ti-group-<?= $groupIndex ?>">
                <div class="ti-group-head">
                    <h2 id="ti-group-<?= $groupIndex ?>"><?= Html::encode($continent) ?></h2>
                    <span><?= count($teams) ?> 支</span>
                </div>
                <ul class="ti-grid">
                    <?php foreach ($teams as $team): ?>
                        <li>
                            <a class="ti-team" href="<?= Html::encode(Url::to(['/team/view', 'id' => $team->id])) ?>">
                                <span class="ti-team-name"><?= Html::encode($team->name) ?></span>
                                <span class="ti-team-code"><?= Html::encode($team->code ?: '—') ?></span>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </section>
        <?php endforeach ?>
    <?php endif ?>
</section>
