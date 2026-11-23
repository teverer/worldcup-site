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
 * @var yii\web\View $this
 * @var string $content
 */

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
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
    <?php $this->head() ?>
</head>
<body class="backend-body backend-<?= Html::encode($controllerId) ?>">
<?php $this->beginBody() ?>

<a class="skip-link" href="#backend-main">跳到主要内容</a>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => '世界杯数据门户 · 后台',
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);

    $menuItems = [
        [
            'label' => '工作台',
            'url' => ['/site/index'],
            'active' => $controllerId === 'site' && $actionId === 'index',
        ],
    ];

    if (!Yii::$app->user->isGuest && Yii::$app->user->identity->role >= common\models\User::ROLE_ADMIN) {
        foreach ($groups as $group) {
            $groupActive = in_array($controllerId, $group['controllers'], true);
            $items = [];
            foreach ($group['items'] as $item) {
                $route = is_array($item['url']) ? ltrim($item['url'][0], '/') : '';
                $target = explode('/', $route)[0];
                $item['active'] = $target === $controllerId;
                $items[] = $item;
            }
            $menuItems[] = [
                'label' => $group['label'],
                'items' => $items,
                'active' => $groupActive,
            ];
        }
    }

    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => '登录', 'url' => ['/site/login']];
    } else {
        $menuItems[] = '<li>'
            . Html::beginForm(['/site/logout'], 'post')
            . Html::submitButton(
                '退出 (' . Yii::$app->user->identity->username . ')',
                ['class' => 'btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';
    }
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => $menuItems,
        'encodeLabels' => false,
    ]);
    NavBar::end();
    ?>

    <div class="container backend-main" id="backend-main">
        <?= Breadcrumbs::widget([
            'homeLink' => ['label' => '工作台', 'url' => Yii::$app->homeUrl],
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer backend-footer">
    <div class="container">
        <p class="pull-left">&copy; <?= date('Y') ?> 国足世界杯夺冠队 · 课程设计</p>
        <p class="pull-right">内容维护后台</p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
