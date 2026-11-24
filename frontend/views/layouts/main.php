<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * Coding by 李行阳 2411353, 20261117
 * 模块:界面设计、框架与团队主页 —— 前台公共布局及团队、下载导航
 *
 * @var yii\web\View $this
 * @var string $content
 */

/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use common\widgets\Alert;

AppAsset::register($this);
$this->registerCssFile('@web/css/theme.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
\yii\bootstrap\BootstrapPluginAsset::register($this);

// 布局专用的少量排布样式;颜色、间距、圆角全部复用 theme.css 的设计令牌。
$this->registerCss(<<<'CSS'
.site-header .navbar { margin-bottom: 0; }
.site-header .site-brand { display: flex; align-items: center; height: auto; padding: 10px 16px 10px 12px; border-left: 2px solid var(--gold); }
@media (max-width: 767px) {
    .site-header .navbar > .container { padding-left: var(--space-2); padding-right: var(--space-2); }
    .site-header .navbar-header { margin-left: 0; margin-right: 0; }
    .site-header .navbar-toggle { margin-right: 0; }
}
.site-main { padding-top: var(--space-5); padding-bottom: var(--space-6); }
.site-brand-text { display: inline-flex; flex-direction: column; line-height: 1.2; }
.site-brand-name { font-weight: 700; letter-spacing: .02em; }
.site-brand-sub { color: rgba(243, 246, 242, .55); font-family: var(--font-display); font-size: 12px; font-weight: 600; letter-spacing: .12em; }
.site-footer-inner { display: flex; flex-wrap: wrap; justify-content: space-between; gap: var(--space-5); }
.site-footer-brand { flex: 0 1 34rem; max-width: 34rem; }
.site-footer-name { color: var(--color-text); font-size: var(--fs-lg); font-weight: 600; }
.site-footer-intro { margin-top: var(--space-1); }
.site-footer-links { display: flex; flex: 0 0 auto; flex-wrap: wrap; gap: var(--space-6); }
@media (max-width: 767px) {
    .site-footer-inner { display: block; }
    .site-footer-brand { max-width: none; margin-bottom: var(--space-3); }
}
.site-footer-title { margin-bottom: var(--space-1); color: var(--color-text); font-weight: 600; }
.site-footer-col ul { margin: 0; padding: 0; list-style: none; }
.site-footer-col li + li { margin-top: 4px; }
.site-footer-bottom { margin-top: var(--space-3); padding-top: var(--space-2); border-top: 1px solid var(--color-border); }
.section-heading-inline { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: var(--space-2); }
/* 说明性入口:与五个档案入口分开,但仍能在页头找到 */
.site-nav .navbar-nav > li.nav-utility { margin-left: 10px; }
.site-nav .navbar-nav > li.nav-utility > a { padding-left: 18px; border-left: 1px solid rgba(243, 246, 242, .22); color: rgba(243, 246, 242, .72); }
.site-nav .navbar-nav > li.nav-utility > a:hover,
.site-nav .navbar-nav > li.nav-utility > a:focus { color: var(--flood); }
/* 说明性入口当前页:只加底线,不抢五项主入口的实心高亮 */
.site-nav .navbar-nav > li.nav-utility.active > a,
.site-nav .navbar-nav > li.nav-utility > a[aria-current="page"] {
    background-color: transparent;
    box-shadow: inset 0 -2px 0 var(--green-light);
    color: var(--flood);
}
@media (max-width: 767px) {
    .site-nav .navbar-nav > li.nav-utility { margin-left: 0; }
    .site-nav .navbar-nav > li.nav-utility > a { padding-left: 12px; border-left: 0; }
}
CSS
);

// 夜场视觉 v2:在 theme.css 与模块 CSS 之后加载,只做覆盖,便于整包回退。
$this->registerCssFile('@web/css/design-v2.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
// 浅色数据页美术层：档案纸、铜线、登记册表格；在 design-v2 之后加载，只做颜色/边框覆盖。
$this->registerCssFile('@web/css/art-r5.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
// R6 夜场统一层：所有前台页回到根页的极光/深绿黑语言；只覆盖颜色与表面。
$this->registerCssFile('@web/css/art-r6.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
$this->registerJsFile('@web/js/atmosphere.js', ['position' => \yii\web\View::POS_END]);

$controllerId = Yii::$app->controller !== null ? Yii::$app->controller->id : '';
$actionId = Yii::$app->controller !== null && Yii::$app->controller->action !== null
    ? Yii::$app->controller->action->id : '';
// 主导航只放「看世界杯」的五个入口；团队、文档、统计口径属于说明性内容，
// 统一收进页脚的「本站」列，避免和档案入口争层级（总纲 §4）。
$navItems = [
    ['label' => '首页', 'url' => ['/site/index'], 'controllers' => ['site', 'tournament'], 'siteIndexOnly' => true],
    ['label' => '比赛', 'url' => ['/game/index'], 'controllers' => ['game']],
    ['label' => '球队', 'url' => ['/team/index'], 'controllers' => ['team']],
    ['label' => '球员', 'url' => ['/player/index'], 'controllers' => ['player']],
    ['label' => '资讯', 'url' => ['/news/index'], 'controllers' => ['news']],
    // 说明性入口只保留一个,放在五项之后并用分隔线弱化;团队、文档、统计口径都在这一页。
    ['label' => '关于与文档', 'url' => ['/team-info/index'], 'controllers' => ['team-info'], 'utility' => true],
];
$pageTitle = $this->title !== null && $this->title !== '' ? $this->title : Yii::$app->name;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($pageTitle) ?> | <?= Html::encode(Yii::$app->name) ?></title>
    <?php // 显示字体在 CSS 之前预加载,避免切页时先看到回退字再跳变 ?>
    <link rel="preload" href="<?= Html::encode(Url::to('@web/fonts/SmileySans-Subset.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= Html::encode(Url::to('@web/fonts/SairaCondensed-700.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <?php $this->head() ?>
</head>
<body class="site-body site-<?= Html::encode($controllerId) ?> site-<?= Html::encode($controllerId) ?>-<?= Html::encode($actionId) ?>">
<?php $this->beginBody() ?>

<div class="atmo" aria-hidden="true">
    <span class="atmo-glow g1"></span>
    <span class="atmo-glow g2"></span>
    <span class="atmo-glow g3"></span>
    <span class="atmo-glow g4"></span>
    <span class="atmo-dots"></span>
    <span class="atmo-pitch"></span>
    <span class="atmo-era"></span>
</div>

<a class="skip-link" href="#main-content">跳到主要内容</a>

<header class="site-header">
    <nav class="navbar navbar-inverse site-nav" aria-label="主导航">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse"
                        data-target="#site-nav" aria-controls="site-nav" aria-expanded="false">
                    <span class="sr-only">展开导航</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand site-brand" href="<?= Html::encode(Yii::$app->homeUrl) ?>">
                    <span class="site-brand-text">
                        <span class="site-brand-name">世界杯数据门户</span>
                        <span class="site-brand-sub">1986—2026</span>
                    </span>
                </a>
            </div>
            <div class="collapse navbar-collapse" id="site-nav">
                <ul class="nav navbar-nav navbar-right">
                    <?php foreach ($navItems as $item): ?>
                        <?php
                        // “届次”在根页与届次详情页保持高亮；登录/关于等 site 页面不误亮。
                        $isActive = in_array($controllerId, $item['controllers'], true);
                        if ($isActive && !empty($item['actions'])) {
                            $isActive = in_array($actionId, $item['actions'], true);
                        }
                        if ($isActive && !empty($item['siteIndexOnly']) && $controllerId === 'site') {
                            $isActive = $actionId === 'index';
                        }
                        ?>
                        <li class="<?= trim(($isActive ? 'active ' : '') . (!empty($item['utility']) ? 'nav-utility' : '')) ?>">
                            <?= Html::a(Html::encode($item['label']), $item['url'], $isActive
                                ? ['aria-current' => 'page']
                                : []) ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="site-main" id="main-content">
    <div class="container">
        <?= Breadcrumbs::widget([
            'homeLink' => ['label' => '首页', 'url' => Yii::$app->homeUrl],
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</main>

<footer class="footer site-footer">
    <div class="container">
        <div class="site-footer-inner">
            <div class="site-footer-brand">
                <p class="site-footer-name">世界杯数据门户</p>
                <p class="site-footer-intro">1986–2026 十一届世界杯 · 届次、球队、球员、比赛与进球。</p>
            </div>
            <nav class="site-footer-links" aria-label="页脚导航">
                <div class="site-footer-col">
                    <p class="site-footer-title">浏览</p>
                    <ul>
                        <li><?= Html::a('首页', Yii::$app->homeUrl) ?></li>
                        <li><?= Html::a('比赛', ['/game/index']) ?></li>
                        <li><?= Html::a('球队', ['/team/index']) ?></li>
                        <li><?= Html::a('球员', ['/player/index']) ?></li>
                        <li><?= Html::a('资讯', ['/news/index']) ?></li>
                    </ul>
                </div>
                <div class="site-footer-col">
                    <p class="site-footer-title">本站</p>
                    <ul>
                        <li><?= Html::a('世界杯留言簿', ['/site/discussion']) ?></li>
                        <li><?= Html::a('统计口径', ['/team-info/index', '#' => 'scope']) ?></li>
                        <li><?= Html::a('源码仓库', 'https://github.com/teverer/worldcup-site', [
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]) ?></li>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
    <div class="site-footer-bottom">
        <div class="container">
            <p>国足世界杯夺冠队 · 课程设计 · 2026</p>
        </div>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage();
