<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261117
 * Coding by 李行阳 2411353, 20261123
 * 模块:框架与团队主页 —— 后台公共布局。
 *
 * 2026-11-23 信息架构调整:13 个平铺菜单按「赛事 / 球队球员 / 内容社区 / 团队」
 * 收成 4 组下拉,首位固定为工作台;当前所在组高亮,减少横向拥挤与找不到入口。
 *
 * 2026-11-23 视觉 v2:后台独立为「工作间」——石墨导轨 + 暖纸工作区,
 * 与前台夜场明确区分;数据密度优先,不做背景动画。
 *
 * @var yii\web\View $this
 * @var string $content
 */

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;

AppAsset::register($this);

$controllerId = Yii::$app->controller !== null ? Yii::$app->controller->id : '';
$actionId = Yii::$app->controller !== null && Yii::$app->controller->action !== null
    ? Yii::$app->controller->action->id : '';

/**
 * 分组定义:每组一个标题 + 若干管理入口。
 * 控制器归属用来决定当前组是否高亮,避免「进了子页却看不出在哪」。
 */
$groups = [
    [
        'label' => '赛事数据',
        'controllers' => ['tournament', 'tournament-team', 'squad', 'game', 'goal'],
        'items' => [
            ['label' => '届次', 'url' => ['/tournament/index']],
            ['label' => '参赛队', 'url' => ['/tournament-team/index']],
            ['label' => '名单', 'url' => ['/squad/index']],
            ['label' => '比赛', 'url' => ['/game/index']],
            ['label' => '进球', 'url' => ['/goal/index']],
        ],
    ],
    [
        'label' => '球队与球员',
        'controllers' => ['team', 'player'],
        'items' => [
            ['label' => '球队', 'url' => ['/team/index']],
            ['label' => '球员', 'url' => ['/player/index']],
        ],
    ],
    [
        'label' => '内容与社区',
        'controllers' => ['news', 'comment'],
        'items' => [
            ['label' => '资讯', 'url' => ['/news/index']],
            ['label' => '留言', 'url' => ['/comment/index']],
        ],
    ],
    [
        'label' => '团队主页',
        'controllers' => ['team-info', 'member', 'work'],
        'items' => [
            ['label' => '团队信息', 'url' => ['/team-info/index']],
            ['label' => '成员', 'url' => ['/member/index']],
            ['label' => '作业文件', 'url' => ['/work/index']],
        ],
    ],
];

$isDashboard = $controllerId === 'site' && $actionId === 'index';
$isAdmin = !Yii::$app->user->isGuest
    && Yii::$app->user->identity->role >= common\models\User::ROLE_ADMIN;

// 后台视觉 v2:在模块 CSS 之后加载,只做覆盖。
$this->registerCssFile('@web/css/design-v2.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
// 后台美术层：纸面表格、铜线标记；在 design-v2 之后加载，只做颜色/边框覆盖。
$this->registerCssFile('@web/css/art-r5.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
$this->registerJsFile('@web/js/workbench.js', ['position' => \yii\web\View::POS_END]);

$groupNo = 2;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> | <?= Html::encode(Yii::$app->name) ?> 后台</title>
    <?php // 显示字体预加载,避免后台切页时字体跳变 ?>
    <link rel="preload" href="<?= Html::encode(Url::to('@web/fonts/SmileySans-Subset.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <?php $this->head() ?>
</head>
<body class="backend-body backend-<?= Html::encode($controllerId) ?> backend-<?= Html::encode($controllerId) ?>-<?= Html::encode($actionId) ?><?= $isDashboard ? ' backend-is-dashboard' : '' ?>">
<?php $this->beginBody() ?>

<a class="skip-link" href="#backend-main">跳到主要内容</a>

<div class="workbench">
    <aside class="wb-rail" id="wb-rail" aria-label="后台导航">
        <div class="wb-brand">
            <a class="wb-brand-link" href="<?= Html::encode(Yii::$app->homeUrl) ?>">
                <span class="wb-brand-mark">WC</span>
                <span class="wb-brand-text">
                    <strong>世界杯档案</strong>
                    <small>WORKBENCH 2026</small>
                </span>
            </a>
        </div>

        <nav class="wb-nav">
            <a class="wb-item<?= $isDashboard ? ' is-active' : '' ?>"
               href="<?= Html::encode(Url::to(['/site/index'])) ?>"
               <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                <span class="wb-item-num">01</span>
                <span class="wb-item-label">工作台</span>
            </a>

            <?php if ($isAdmin): ?>
                <?php foreach ($groups as $group): ?>
                    <?php
                    $groupActive = in_array($controllerId, $group['controllers'], true);
                    $groupCode = sprintf('%02d', $groupNo++);
                    ?>
                    <div class="wb-group<?= $groupActive ? ' is-current' : '' ?>">
                        <p class="wb-group-title">
                            <span><?= Html::encode($groupCode) ?></span>
                            <?= Html::encode($group['label']) ?>
                        </p>
                        <?php foreach ($group['items'] as $item): ?>
                            <?php
                            $route = is_array($item['url']) ? ltrim($item['url'][0], '/') : '';
                            $target = explode('/', $route)[0];
                            $itemActive = $target === $controllerId;
                            ?>
                            <a class="wb-item<?= $itemActive ? ' is-active' : '' ?>"
                               href="<?= Html::encode(Url::to($item['url'])) ?>"
                               <?= $itemActive ? 'aria-current="page"' : '' ?>>
                                <span class="wb-item-label"><?= Html::encode($item['label']) ?></span>
                            </a>
                        <?php endforeach ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </nav>

        <div class="wb-rail-foot">
            <?php if (Yii::$app->user->isGuest): ?>
                <?= Html::a('登录后台', ['/site/login'], ['class' => 'wb-login']) ?>
            <?php else: ?>
                <?php $identity = Yii::$app->user->identity; ?>
                <div class="wb-user">
                    <span class="wb-user-avatar"><?= Html::encode(mb_strtoupper(mb_substr($identity->username, 0, 1))) ?></span>
                    <span class="wb-user-text">
                        <strong><?= Html::encode($identity->username) ?></strong>
                        <small><?= $identity->role >= common\models\User::ROLE_ADMIN ? '管理员' : '编辑' ?></small>
                    </span>
                </div>
                <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'wb-logout-form']) ?>
                <?= Html::submitButton('退出登录', ['class' => 'wb-logout']) ?>
                <?= Html::endForm() ?>
            <?php endif ?>
            <p class="wb-build">VISUAL V2 · 工作间</p>
        </div>
    </aside>

    <div class="wb-scrim" data-rail-close></div>

    <div class="wb-main">
        <header class="wb-top">
            <button class="wb-rail-toggle" type="button" data-rail-toggle aria-controls="wb-rail" aria-expanded="false">
                <span class="wb-rail-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span>
                <span class="sr-only">展开后台导航</span>
            </button>

            <div class="wb-crumbs-wrap">
                <?php if (empty($this->params['breadcrumbs']) && $isDashboard): ?>
                    <nav class="wb-crumbs" aria-label="面包屑">
                        <span class="wb-crumb-current">工作台</span>
                    </nav>
                <?php else: ?>
                    <?= Breadcrumbs::widget([
                        'homeLink' => ['label' => '工作台', 'url' => Yii::$app->homeUrl],
                        'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                        'options' => ['class' => 'breadcrumb wb-crumbs'],
                    ]) ?>
                <?php endif ?>
            </div>

            <div class="wb-top-meta">
                <span class="wb-status"><i aria-hidden="true"></i> 内容维护</span>
                <span class="wb-date"><?= date('Y.m.d') ?></span>
            </div>
        </header>

        <main class="backend-main" id="backend-main">
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>

        <footer class="wb-footer">
            <span>&copy; <?= date('Y') ?> 国足世界杯夺冠队 · 课程设计</span>
            <span>内容维护后台 · 数据只在这里改</span>
        </footer>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
