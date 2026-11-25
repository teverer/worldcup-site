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

use common\components\TeamFlag;
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
        <div class="pl-head-main">
            <h1>球队</h1>
        </div>
        <div class="idx-total" aria-label="共 <?= (int) $total ?> 支国家队">
            <b><?= (int) $total ?></b><span>支国家队</span>
        </div>
    </header>

    <?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
    <?php // GET 表单会丢掉 action 里的 ?r=，必须让 beginForm 生成 hidden r；否则提交后落到首页。 ?>
    <?= Html::beginForm(['index'], 'get', ['class' => 'pl-search', 'role' => 'search']) ?>
        <div class="pl-search-main">
            <label class="sr-only" for="team-q">球队名称或 FIFA 代码</label>
            <input class="form-control pl-input" type="search" id="team-q" name="q"
                   value="<?= Html::encode((string) $searchModel->q) ?>"
                   placeholder="输入球队名或代码，如 巴西、BRA" maxlength="100">
            <button class="btn btn-primary" type="submit">查找球队</button>
        </div>
        <div class="pl-search-filters">
            <label class="sr-only" for="team-continent">所属大洲</label>
            <select class="form-control pl-continent" id="team-continent" name="continent">
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
    <?= Html::endForm() ?>

    <?php if ($hasFilter): ?>
        <p class="ti-summary">找到 <?= (int) $shown ?> 支球队。</p>
    <?php endif ?>

    <?php if (count($groups) > 1): ?>
        <nav class="td-editions ti-jump" aria-label="按大洲跳转">
            <span class="ti-jump-label">直接到大洲</span>
            <?php $jumpIndex = 0; foreach ($groups as $continent => $teams): $jumpIndex++; ?>
                <a class="td-edition" href="#ti-group-<?= $jumpIndex ?>"><?= Html::encode($continent) ?></a>
            <?php endforeach ?>
        </nav>
    <?php endif ?>

    <?php if (!$groups): ?>
        <div class="empty-state pl-empty">
            <h3 role="heading" aria-level="2">没有找到球队</h3>
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
                            <a class="ti-team" href="<?= Html::encode(Url::to(['/team/view', 'id' => $team->id])) ?>"
                               title="<?= Html::encode($team->name) ?>">
                                <span class="ti-team-id">
                                    <?= TeamFlag::html($team->code, $team->name) ?>
                                    <span class="ti-team-name"><?= Html::encode($team->name) ?></span>
                                    <span class="ti-team-code"><?= Html::encode($team->code ?: '—') ?></span>
                                </span>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </section>
        <?php endforeach ?>
    <?php endif ?>
</section>
