<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:界面设计 —— 后台工作台：关键数据、最近留言、待补内容、快速操作。
 *
 * 结构借 AdminLTE 2 的 Info Box / Box 与 Gentelella 的「数据带 + 内容盒」，
 * 只保留能指导下一步动作的信息；社区统计不再在页脚重复第二遍。
 *
 * @var yii\web\View $this
 * @var array $counts 各表记录数
 * @var array $gaps 待补内容
 * @var int $todayComments 今天新增留言
 * @var int $monthNews 本月新增资讯
 * @var int $positionRate 球员位置填写率（0–100）
 * @var float $goalsPerGame 场均进球
 * @var common\models\Comment[] $recentComments
 * @var common\models\News[] $recentNews
 */

use common\components\CommentTarget;
use common\models\Comment;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;

$this->title = '工作台';

$icons = [
    'comment' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'news' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h6"/>',
    'player' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    'game' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'goal' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>',
];
$icon = static function (string $name) use ($icons): string {
    $path = $icons[$name] ?? '<circle cx="12" cy="12" r="2"/>';
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg>';
};

$kpis = [
    [
        'label' => '留言',
        'value' => (int) $counts['comment'],
        'meta' => '今天 +' . (int) $todayComments,
        'url' => ['/comment/index'],
        'icon' => 'comment',
        'tone' => 'gold',
        'progress' => null,
    ],
    [
        'label' => '资讯',
        'value' => (int) $counts['news'],
        'meta' => '本月 +' . (int) $monthNews,
        'url' => ['/news/index'],
        'icon' => 'news',
        'tone' => '',
        'progress' => null,
    ],
    [
        'label' => '球员',
        'value' => (int) $counts['player'],
        'meta' => '位置已填 ' . (int) $positionRate . '%',
        'url' => ['/player/index'],
        'icon' => 'player',
        'tone' => '',
        'progress' => (int) $positionRate,
    ],
    [
        'label' => '比赛',
        'value' => (int) $counts['game'],
        'meta' => '场均 ' . $goalsPerGame . ' 球',
        'url' => ['/game/index'],
        'icon' => 'game',
        'tone' => 'red',
        'progress' => null,
    ],
];

?>
<div class="dash">
    <header class="dash-header">
        <div>
            <h1 class="dash-title">工作台</h1>
            <p class="dash-lead">赛事、球队球员、资讯与社区。</p>
        </div>
        <div class="dash-actions">
            <?php /* 「查看前台」已经在顶栏，工作台标题右侧不再重复第二个入口。 */ ?>
            <?= Html::a('新增比赛', ['/game/create'], ['class' => 'btn btn-primary btn-sm']) ?>
        </div>
    </header>

    <section class="dash-kpis" aria-label="关键数据">
        <?php foreach ($kpis as $kpi): ?>
            <a class="info-box<?= $kpi['tone'] !== '' ? ' info-box--' . Html::encode($kpi['tone']) : '' ?>"
               href="<?= Html::encode(Url::to($kpi['url'])) ?>">
                <span class="info-box-icon" aria-hidden="true"><?= $icon($kpi['icon']) ?></span>
                <span class="info-box-content">
                    <span class="info-box-text"><?= Html::encode($kpi['label']) ?></span>
                    <span class="info-box-number"><?= Html::encode((string) $kpi['value']) ?></span>
                    <span class="info-box-meta"><?= Html::encode($kpi['meta']) ?></span>
                    <?php if ($kpi['progress'] !== null): ?>
                        <span class="dash-progress" aria-hidden="true">
                            <span style="width: <?= (int) $kpi['progress'] ?>%"></span>
                        </span>
                    <?php endif ?>
                </span>
            </a>
        <?php endforeach ?>
    </section>

    <div class="dash-columns">
        <div class="dash-main">
            <section class="box box--accent" aria-labelledby="dash-comments">
                <div class="box-header with-border">
                    <h2 class="box-title" id="dash-comments">最近留言</h2>
                    <?= Html::a('全部留言', ['/comment/index'], ['class' => 'box-link']) ?>
                </div>
                <?php if ($recentComments): ?>
                    <div class="box-body no-padding">
                        <table class="dash-table">
                            <thead>
                            <tr>
                                <th scope="col">用户</th>
                                <th scope="col">留言</th>
                                <th scope="col" class="dash-when">时间</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recentComments as $comment): ?>
                                <?php
                                $author = $comment->user === null
                                    ? '未知用户'
                                    : ($comment->user->nickname ?: $comment->user->username);
                                $initial = mb_strtoupper(mb_substr($author, 0, 1));
                                ?>
                                <tr>
                                    <td>
                                        <span class="dash-user">
                                            <span class="dash-avatar" aria-hidden="true"><?= Html::encode($initial) ?></span>
                                            <span class="dash-user-name"><?= Html::encode($author) ?></span>
                                        </span>
                                    </td>
                                    <td class="dash-cell-main">
                                        <span class="dash-comment-text">
                                            <?= Html::encode(StringHelper::truncate($comment->content, 48, '…', null, true)) ?>
                                        </span>
                                        <span class="dash-comment-target">
                                            <?= Html::encode(CommentTarget::label($comment->target_type)) ?>
                                            #<?= (int) $comment->target_id ?>
                                            <?php if ($comment->status === Comment::STATUS_DELETED): ?>
                                                <span class="dash-status">已删除</span>
                                            <?php endif ?>
                                        </span>
                                    </td>
                                    <td class="dash-when">
                                        <span class="dash-time">
                                            <?= Html::encode(Yii::$app->formatter->asDatetime($comment->create_time)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="dash-empty">
                        还没有留言。<?= Html::a('去留言管理', ['/comment/index']) ?>
                    </div>
                <?php endif ?>
            </section>

            <section class="box box--gold" aria-labelledby="dash-gaps">
                <div class="box-header with-border">
                    <h2 class="box-title" id="dash-gaps">待补内容</h2>
                    <span class="box-badge<?= $gaps ? ' box-badge--alert' : '' ?>"><?= count($gaps) ?> 项</span>
                </div>
                <?php if (!$gaps): ?>
                    <div class="box-body">
                        <div class="dash-done">
                            <strong>这一轮没有明显缺口。</strong>
                            <p>可以去<?= Html::a('写一篇资讯', ['/news/create']) ?>，或者检查<?= Html::a('最新比赛', ['/game/index']) ?>的记录。</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="box-body no-padding">
                        <ul class="dash-tasks">
                            <?php foreach ($gaps as $gap): ?>
                                <li>
                                    <a class="dash-task" href="<?= Html::encode(Url::to($gap['url'])) ?>">
                                        <span class="dash-task-text">
                                            <strong><?= Html::encode($gap['title']) ?></strong>
                                            <span><?= Html::encode($gap['detail']) ?></span>
                                        </span>
                                        <span class="dash-task-action"><?= Html::encode($gap['action']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif ?>
            </section>
        </div>

        <aside class="dash-side">
            <section class="box" aria-labelledby="dash-quick">
                <div class="box-header with-border">
                    <h2 class="box-title" id="dash-quick">快速操作</h2>
                </div>
                <div class="box-body">
                    <div class="dash-quick-grid">
                        <a class="dash-quick" href="<?= Html::encode(Url::to(['/game/create'])) ?>">
                            <?= $icon('plus') ?><span>新增比赛</span>
                        </a>
                        <a class="dash-quick" href="<?= Html::encode(Url::to(['/goal/create'])) ?>">
                            <?= $icon('goal') ?><span>新增进球</span>
                        </a>
                        <a class="dash-quick" href="<?= Html::encode(Url::to(['/player/create'])) ?>">
                            <?= $icon('player') ?><span>新增球员</span>
                        </a>
                        <a class="dash-quick" href="<?= Html::encode(Url::to(['/news/create'])) ?>">
                            <?= $icon('news') ?><span>写资讯</span>
                        </a>
                    </div>
                </div>
            </section>

            <section class="box" aria-labelledby="dash-news">
                <div class="box-header with-border">
                    <h2 class="box-title" id="dash-news">最近资讯</h2>
                    <?= Html::a('全部资讯', ['/news/index'], ['class' => 'box-link']) ?>
                </div>
                <?php if ($recentNews): ?>
                    <div class="box-body no-padding">
                        <ul class="dash-news">
                            <?php foreach ($recentNews as $news): ?>
                                <li>
                                    <a href="<?= Html::encode(Url::to(['/news/view', 'id' => $news->id])) ?>">
                                        <span class="dash-news-title"><?= Html::encode($news->title) ?></span>
                                        <span class="dash-news-time">
                                            <?= Html::encode(Yii::$app->formatter->asDate($news->create_time)) ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php else: ?>
                    <div class="dash-empty">
                        还没有资讯。<?= Html::a('写第一篇', ['/news/create']) ?>
                    </div>
                <?php endif ?>
            </section>
        </aside>
    </div>
</div>
