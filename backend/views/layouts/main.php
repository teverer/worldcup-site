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
// 全站确认框 / flash 轻提示：与前台共用同一份原生 <dialog> 实现。
\common\assets\WcUiAsset::register($this);

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

/**
 * 侧栏图标的线稿路径：只保留 24×24 的简单几何，不引 Font Awesome。
 * 用标签名映射，避免在分组数组里再堆一层图标字段。
 */
$wbIconPaths = [
    'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    '届次' => '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M7 5H4v2a4 4 0 0 0 4 4M17 5h3v2a4 4 0 0 1-4 4"/>',
    '参赛队' => '<path d="M5 21V4M5 5h11l-2 4 2 4H5"/>',
    '名单' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/>',
    '比赛' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 11h18"/>',
    '进球' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>',
    '球队' => '<path d="M12 3 5 6v5c0 5 3 8 7 10 4-2 7-5 7-10V6z"/>',
    '球员' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    '资讯' => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h6"/>',
    '留言' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    '团队信息' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    '成员' => '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M21 21v-2a4 4 0 0 0-3-3.87"/>',
    '作业文件' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
];
$wbIcon = static function (string $name) use ($wbIconPaths): string {
    $path = $wbIconPaths[$name] ?? '<circle cx="12" cy="12" r="2"/>';
    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $path . '</svg>';
};

$isDashboard = $controllerId === 'site' && $actionId === 'index';
$isAdmin = !Yii::$app->user->isGuest
    && Yii::$app->user->identity->role >= common\models\User::ROLE_ADMIN;
// 前台与后台是两个 docroot：用配置里的前台地址，不在后台拼相对路由。
$frontendHome = rtrim((string) (Yii::$app->params['frontendUrl'] ?? 'http://localhost:8080'), '/') . '/';

// 后台视觉 v2:在模块 CSS 之后加载,只做覆盖。
$this->registerCssFile('@web/css/design-v2.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
// 后台美术层：纸面表格、铜线标记；在 design-v2 之后加载，只做颜色/边框覆盖。
$this->registerCssFile('@web/css/art-r5.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
// 工作间结构层：页头工具条、筛选面板、当前筛选、空态（r7）。
$this->registerCssFile('@web/css/r7-admin.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
// R10：向成熟后台模板的结构靠拢，并把颜色拉回前台的夜蓝 / 球场绿 / 哑光金 / 冷纸。
$adminR10Version = @filemtime(Yii::getAlias('@webroot/css/admin-r10.css')) ?: '';
$this->registerCssFile(
    Url::to('@web/css/admin-r10.css') . ($adminR10Version !== '' ? '?v=' . $adminR10Version : ''),
    ['depends' => [\yii\bootstrap\BootstrapAsset::class]]
);
// AdminLTE 2（MIT）只摘出 Info Box / Box 两组组件；许可见 css/vendor/AdminLTE-LICENSE.txt。
// 组件类名与 DOM 契约保持原样，颜色 / 圆角 / 字号由 admin-r12.css 覆盖。
$adminLteVersion = @filemtime(Yii::getAlias('@webroot/css/vendor/adminlte-components.css')) ?: '';
$this->registerCssFile(
    Url::to('@web/css/vendor/adminlte-components.css')
        . ($adminLteVersion !== '' ? '?v=' . $adminLteVersion : ''),
    ['depends' => [\yii\bootstrap\BootstrapAsset::class]]
);
// R12：侧栏图标与折叠、顶栏前台入口、Info Box / Box 的本站皮肤。
$adminR12Version = @filemtime(Yii::getAlias('@webroot/css/admin-r12.css')) ?: '';
$this->registerCssFile(
    Url::to('@web/css/admin-r12.css') . ($adminR12Version !== '' ? '?v=' . $adminR12Version : ''),
    ['depends' => [\yii\bootstrap\BootstrapAsset::class]]
);
// 工作台自己的排布只在这一页加载，避免把 dashboard 的栅格带到列表页。
if ($isDashboard) {
    $dashboardVersion = @filemtime(Yii::getAlias('@webroot/css/dashboard.css')) ?: '';
    $this->registerCssFile(
        Url::to('@web/css/dashboard.css') . ($dashboardVersion !== '' ? '?v=' . $dashboardVersion : ''),
        ['depends' => [\yii\bootstrap\BootstrapAsset::class]]
    );
}
$this->registerJsFile('@web/js/workbench.js', ['position' => \yii\web\View::POS_END]);
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
    <link rel="preload" href="<?= Html::encode(Url::to('@web/fonts/SairaCondensed-700.woff2')) ?>" as="font" type="font/woff2" crossorigin>
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
                    <strong>世界杯40年概展</strong>
                </span>
            </a>
        </div>

        <nav class="wb-nav">
            <a class="wb-item<?= $isDashboard ? ' is-active' : '' ?>"
               href="<?= Html::encode(Url::to(['/site/index'])) ?>"
               <?= $isDashboard ? 'aria-current="page"' : '' ?>>
                <span class="wb-item-icon" aria-hidden="true"><?= $wbIcon('dashboard') ?></span>
                <span class="wb-item-label">工作台</span>
            </a>

            <?php if ($isAdmin): ?>
                <?php foreach ($groups as $groupIndex => $group): ?>
                    <?php $groupActive = in_array($controllerId, $group['controllers'], true); ?>
                    <div class="wb-group<?= $groupActive ? ' is-current' : '' ?>"
                         data-wb-group="<?= Html::encode($group['label']) ?>">
                        <button class="wb-group-toggle" type="button" aria-expanded="true"
                                aria-controls="wb-group-<?= (int) $groupIndex ?>">
                            <span class="wb-group-title"><?= Html::encode($group['label']) ?></span>
                            <svg class="wb-chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                        <div class="wb-group-items" id="wb-group-<?= (int) $groupIndex ?>">
                            <?php foreach ($group['items'] as $item): ?>
                                <?php
                                $route = is_array($item['url']) ? ltrim($item['url'][0], '/') : '';
                                $target = explode('/', $route)[0];
                                $itemActive = $target === $controllerId;
                                ?>
                                <a class="wb-item<?= $itemActive ? ' is-active' : '' ?>"
                                   href="<?= Html::encode(Url::to($item['url'])) ?>"
                                   <?= $itemActive ? 'aria-current="page"' : '' ?>>
                                    <span class="wb-item-icon" aria-hidden="true"><?= $wbIcon($item['label']) ?></span>
                                    <span class="wb-item-label"><?= Html::encode($item['label']) ?></span>
                                </a>
                            <?php endforeach ?>
                        </div>
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

            <div class="wb-top-actions">
                <a class="wb-top-link" href="<?= Html::encode($frontendHome) ?>" target="_blank" rel="noopener">
                    查看前台
                </a>
            </div>
        </header>

        <main class="backend-main" id="backend-main">
            <?= Alert::widget() ?>
            <?= $content ?>
        </main>

        <footer class="wb-footer">
            <span>&copy; <?= date('Y') ?> 国足世界杯夺冠队 · 课程设计</span>
        </footer>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
