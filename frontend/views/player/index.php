<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 球员索引:搜索优先,默认展示每届最佳射手。
 *
 * @var yii\web\View $this
 * @var common\models\PlayerSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $teams
 * @var array $positions
 * @var array $spotlight
 * @var int $totalPlayers
 */

use common\components\TeamFlag;
use common\models\Player;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = '球员';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCssFile('@web/css/catalog.css');

$hasFilter = trim((string) $searchModel->q) !== ''
    || ($searchModel->teamId !== null && $searchModel->teamId !== '')
    || trim((string) $searchModel->position) !== '';
$isAll = Yii::$app->request->get('all') === '1';
?>
<section class="catalog-page player-index">
    <header class="pl-head">
        <div class="pl-head-main">
            <h1>球员</h1>
            <p>跨越十一届；先搜中文名或原名，不记得名字，就从每届最佳射手进入。</p>
        </div>
        <div class="idx-total" aria-label="共 <?= (int) $totalPlayers ?> 名球员">
            <b><?= (int) $totalPlayers ?></b><span>名球员</span>
        </div>
    </header>

    <?= Html::errorSummary($searchModel, ['class' => 'alert alert-danger']) ?>
    <form class="pl-search" method="get" action="<?= Html::encode(Url::to(['/player/index'])) ?>" role="search">
        <div class="pl-search-main">
            <label class="sr-only" for="pl-q">球员姓名或国籍</label>
            <input class="form-control pl-input" type="search" id="pl-q" name="q"
                   value="<?= Html::encode((string) $searchModel->q) ?>"
                   placeholder="输入中文名、原名或国籍，如 姆巴佩、Messi、阿根廷" maxlength="100">
            <button class="btn btn-primary" type="submit">查找球员</button>
        </div>
        <div class="pl-search-filters">
            <label class="sr-only" for="pl-team">代表球队</label>
            <select class="form-control" id="pl-team" name="teamId">
                <option value="">全部球队</option>
                <?php foreach ($teams as $teamId => $teamName): ?>
                    <option value="<?= (int) $teamId ?>"<?= (string) $searchModel->teamId === (string) $teamId ? ' selected' : '' ?>>
                        <?= Html::encode($teamName) ?>
                    </option>
                <?php endforeach ?>
            </select>
            <label class="sr-only" for="pl-position">主要位置</label>
            <select class="form-control" id="pl-position" name="position">
                <option value="">全部位置</option>
                <?php foreach ($positions as $position): ?>
                    <option value="<?= Html::encode($position) ?>"<?= $searchModel->position === $position ? ' selected' : '' ?>>
                        <?= Html::encode($position) ?>
                    </option>
                <?php endforeach ?>
            </select>
            <?php if ($hasFilter): ?>
                <?= Html::a('清除条件', ['/player/index'], ['class' => 'pl-clear']) ?>
            <?php endif ?>
            <?= $this->render('//partials/_name_lang') ?>
        </div>
    </form>

    <?php if ($spotlight): ?>
        <section class="pl-section" aria-labelledby="pl-spotlight-title">
            <div class="pl-section-head">
                <div>
                    <h2 id="pl-spotlight-title">每届最佳射手</h2>
                    <p>每届进球最多的球员；默认显示原名，可在上方切换中文名；未收录译名的球员保留原名。</p>
                </div>
            </div>

            <ol class="pl-scorers">
                <?php foreach ($spotlight as $leader): ?>
                    <li>
                        <a class="pl-scorer" href="<?= Html::encode(Url::to(['/player/view', 'id' => $leader['player_id']])) ?>">
                            <span class="pl-scorer-year"><?= Html::encode((string) $leader['year']) ?></span>
                            <span class="pl-scorer-text">
                                <strong><?= Html::encode(Player::formatName($leader['name'], $leader['name_zh'], $leader['nationality'])) ?></strong>
                                <span>
                                    <?= TeamFlag::html(isset($leader['team_code']) ? $leader['team_code'] : null, (string) $leader['team_name'], 'pl-scorer-flag') ?>
                                    <?= Html::encode($leader['team_name'] ?: ($leader['nationality'] ?: '球队待补')) ?>
                                    <?php if ($leader['tied'] > 1): ?>
                                        · 并列 <?= (int) $leader['tied'] ?> 人
                                    <?php endif ?>
                                </span>
                            </span>
                            <span class="pl-scorer-goals"><?= (int) $leader['goals'] ?><small>球</small></span>
                        </a>
                    </li>
                <?php endforeach ?>
                <?php // 11 届正好差一格；第 12 格做成“全部球员”，两列收齐，不留孤行。 ?>
                <li>
                    <a class="pl-scorer pl-scorer--all" href="<?= Html::encode(Url::to(['/player/index', 'all' => 1])) ?>">
                        <span class="pl-scorer-year">全部</span>
                        <span class="pl-scorer-text">
                            <strong>浏览全部 <?= (int) $totalPlayers ?> 名球员</strong>
                            <span>按姓名、球队、位置继续筛</span>
                        </span>
                    </a>
                </li>
            </ol>
        </section>
    <?php else: ?>
        <section class="pl-section" aria-labelledby="pl-results-title">
            <div class="pl-section-head">
                <div>
                    <h2 id="pl-results-title"><?= $isAll ? '全部球员' : '搜索结果' ?></h2>
                    <?php if ($dataProvider->totalCount > 0): ?>
                        <p>
                            共 <?= (int) $dataProvider->totalCount ?> 人<?php if ($dataProvider->totalCount > $dataProvider->pagination->pageSize): ?>，
                            当前显示 <?= (int) $dataProvider->pagination->getOffset() + 1 ?>–
                            <?= (int) $dataProvider->pagination->getOffset() + count($dataProvider->getModels()) ?> 人<?php endif ?>。
                        </p>
                    <?php else: ?>
                        <p>没有符合当前条件的球员。</p>
                    <?php endif ?>
                </div>
                <?= Html::a('回到每届最佳射手', ['/player/index'], ['class' => 'pl-section-link']) ?>
            </div>

            <?php if (!$dataProvider->getModels()): ?>
                <div class="empty-state pl-empty">
                    <h3>没有找到球员</h3>
                    <p>换一个姓名或国籍，或者放宽球队、位置条件。</p>
                    <p><?= Html::a('清除全部条件', ['/player/index']) ?> · <?= Html::a('按球队浏览', ['/team/index']) ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table pl-table">
                        <caption class="sr-only"><?= $isAll ? '全部球员' : '球员搜索结果' ?></caption>
                        <thead>
                            <tr>
                                <th scope="col">球员</th>
                                <th scope="col">国籍</th>
                                <th scope="col">主要位置</th>
                                <th scope="col">出生日期</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dataProvider->getModels() as $player): ?>
                                <tr>
                                    <td>
                                        <?= Html::a(Html::encode($player->displayName()), ['/player/view', 'id' => $player->id], [
                                            'class' => 'pl-name',
                                            'title' => $player->name,
                                        ]) ?>
                                    </td>
                                    <td><?= Html::encode($player->nationality ?: '—') ?></td>
                                    <td><?= Html::encode($player->position ?: '待补') ?></td>
                                    <td><?= Html::encode($player->birth_date ?: '—') ?></td>
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
    <?php endif ?>
</section>
