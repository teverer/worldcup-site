<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261117
 * 模块:界面设计 —— 前台公共布局(页头、导航、页脚)
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
.site-header .site-brand { display: flex; align-items: center; gap: 8px; height: auto; padding-top: 10px; padding-bottom: 10px; }
.site-main { padding-top: var(--space-5); padding-bottom: var(--space-6); }
.site-brand-logo { flex: 0 0 auto; vertical-align: middle; }
.site-brand-text { display: inline-flex; flex-direction: column; vertical-align: middle; line-height: 1.2; }
.site-brand-name { font-weight: 600; }
.site-brand-sub { color: rgba(255, 255, 255, .72); font-size: 12px; letter-spacing: .04em; }
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
CSS
);

$controllerId = Yii::$app->controller !== null ? Yii::$app->controller->id : '';
$actionId = Yii::$app->controller !== null && Yii::$app->controller->action !== null
    ? Yii::$app->controller->action->id : '';
$navItems = [
    ['label' => '首页', 'url' => ['/site/index'], 'controller' => 'site'],
    ['label' => '届次', 'url' => ['/tournament/index'], 'controller' => 'tournament'],
    ['label' => '比赛', 'url' => ['/game/index'], 'controller' => 'game'],
    ['label' => '球队', 'url' => ['/team/index'], 'controller' => 'team'],
    ['label' => '球员', 'url' => ['/player/index'], 'controller' => 'player'],
    ['label' => '资讯', 'url' => ['/news/index'], 'controller' => 'news'],
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
    <?php $this->head() ?>
</head>
<body class="site-body site-<?= Html::encode($controllerId) ?> site-<?= Html::encode($controllerId) ?>-<?= Html::encode($actionId) ?>">
<?php $this->beginBody() ?>

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
                    <span class="site-brand-logo" aria-hidden="true"
                          style="display: inline-flex; align-items: center; justify-content: center; width: 44px; height: 44px; border-radius: 50%; background: var(--color-primary, #0e7c4a); color: var(--color-accent, #f2b705); font-weight: 700; font-size: 15px; letter-spacing: .5px;">WC</span>
                    <span class="site-brand-text">
                        <span class="site-brand-name">世界杯数据门户</span>
                        <span class="site-brand-sub">1986–2026</span>
                    </span>
                </a>
            </div>
            <div class="collapse navbar-collapse" id="site-nav">
                <ul class="nav navbar-nav navbar-right">
                    <?php foreach ($navItems as $item): ?>
                        <?php
                        // 首页只在 site/index 高亮,登录/关于等页面不误亮“首页”。
                        $isActive = $controllerId === $item['controller']
                            && ($item['controller'] !== 'site' || $actionId === 'index');
                        ?>
                        <li<?= $isActive ? ' class="active"' : '' ?>>
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
                <p class="site-footer-intro">收录 1986–2026 年十一届世界杯的届次、球队、球员、比赛与进球数据,用数据回看绿茵经典。</p>
            </div>
            <div class="site-footer-links">
                <div class="site-footer-col">
                    <p class="site-footer-title">文档</p>
                    <ul>
                        <li><?= Html::a('项目文档', 'https://github.com/teverer/worldcup-site/tree/dev/docs', [
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]) ?></li>
                        <li><?= Html::a('源码仓库', 'https://github.com/teverer/worldcup-site', [
                            'target' => '_blank',
                            'rel' => 'noopener noreferrer',
                        ]) ?></li>
                    </ul>
                </div>
                <div class="site-footer-col">
                    <p class="site-footer-title">关于</p>
                    <ul>
                        <li><?= Html::a('团队展示', Url::to(['/site/about'])) ?></li>
                        <li><?= Html::a('最新资讯', ['/news/index']) ?></li>
                    </ul>
                </div>
            </div>
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
