<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:界面设计 —— 后台工作台:数据总览、待补内容、快速新增。
 *
 * @var yii\web\View $this
 * @var array $counts 各表记录数
 * @var array $gaps 待补内容
 * @var string $today
 */

use yii\helpers\Html;

$this->title = '工作台';
$this->registerCssFile('@web/css/dashboard.css');

$stats = [
    ['key' => 'tournament', 'label' => '届次', 'hint' => '1986—2026', 'url' => ['/tournament/index']],
    ['key' => 'game', 'label' => '比赛', 'hint' => '含小组赛与淘汰赛', 'url' => ['/game/index']],
    ['key' => 'goal', 'label' => '进球', 'hint' => '按球员与球队归档', 'url' => ['/goal/index']],
    ['key' => 'team', 'label' => '球队', 'hint' => '参赛国家队', 'url' => ['/team/index']],
    ['key' => 'player', 'label' => '球员', 'hint' => '历届名单去重', 'url' => ['/player/index']],
    ['key' => 'news', 'label' => '资讯', 'hint' => '赛事回顾与观察', 'url' => ['/news/index']],
];
?>
<div class="dash">
    <header class="dash-header">
        <div>
            <h1 class="dash-title">工作台</h1>
            <p class="dash-lead">维护赛事数据、球队球员、资讯与社区。先看总览,再处理下面缺的内容。</p>
        </div>
        <p class="dash-meta"><?= Html::encode($today) ?> · 已收录 <?= (int) $counts['tournament'] ?> 届</p>
    </header>

    <section class="dash-section" aria-labelledby="dash-overview">
        <div class="dash-section-head">
            <h2 id="dash-overview">数据总览</h2>
            <p>点数字进入对应的管理页。</p>
        </div>
        <div class="dash-stats">
            <?php foreach ($stats as $stat): ?>
                <a class="dash-stat" href="<?= Html::encode(\yii\helpers\Url::to($stat['url'])) ?>">
                    <span class="dash-stat-value"><?= Html::encode((string) $counts[$stat['key']]) ?></span>
                    <span class="dash-stat-label"><?= Html::encode($stat['label']) ?></span>
                    <span class="dash-stat-hint"><?= Html::encode($stat['hint']) ?></span>
                </a>
            <?php endforeach ?>
        </div>
    </section>

    <div class="dash-columns">
        <section class="dash-panel" aria-labelledby="dash-gaps">
            <div class="dash-panel-head">
                <h2 id="dash-gaps">待补内容</h2>
                <span class="badge <?= $gaps ? 'badge-accent' : 'badge-primary' ?>"><?= count($gaps) ?> 项</span>
            </div>

            <?php if (!$gaps): ?>
                <div class="dash-done">
                    <strong>这一轮没有明显缺口。</strong>
                    <p>可以去<?= Html::a('写一篇资讯', ['/news/create']) ?>，或者检查<?= Html::a('最新比赛', ['/game/index']) ?>的记录。</p>
                </div>
            <?php else: ?>
                <ul class="dash-tasks">
                    <?php foreach ($gaps as $gap): ?>
                        <li>
                            <a class="dash-task" href="<?= Html::encode(\yii\helpers\Url::to($gap['url'])) ?>">
                                <span class="dash-task-text">
                                    <strong><?= Html::encode($gap['title']) ?></strong>
                                    <span><?= Html::encode($gap['detail']) ?></span>
                                </span>
                                <span class="dash-task-action"><?= Html::encode($gap['action']) ?></span>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            <?php endif ?>
        </section>

        <aside class="dash-panel dash-panel-side" aria-labelledby="dash-quick">
            <div class="dash-panel-head">
                <h2 id="dash-quick">快速新增</h2>
            </div>
            <ul class="dash-quick">
                <li><?= Html::a('新增比赛', ['/game/create']) ?><span>比分、阶段、场地</span></li>
                <li><?= Html::a('新增进球', ['/goal/create']) ?><span>球员、时间、方式</span></li>
                <li><?= Html::a('新增球员', ['/player/create']) ?><span>姓名、位置、国籍</span></li>
                <li><?= Html::a('写资讯', ['/news/create']) ?><span>标题、正文、封面</span></li>
            </ul>

            <div class="dash-foot">
                <p>社区：<?= (int) $counts['comment'] ?> 条留言 · <?= (int) $counts['member'] ?> 位成员 · <?= (int) $counts['work'] ?> 个作业文件</p>
                <p><?= Html::a('管理留言', ['/comment/index']) ?> · <?= Html::a('管理成员', ['/member/index']) ?></p>
            </div>
        </aside>
    </div>
</div>
