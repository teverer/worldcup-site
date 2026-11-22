<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李佳婕 2413745, 20261122
 * Coding by Codex (AI assistance), 20261021
 * Coding by 石子川 2411966, 20261117
 * Coding by 李行阳 2411353, 20261115
 * 模块:球队与球员、留言与资讯 —— 在后台布局中添加资料、资讯和留言管理导航。
 * 模块:框架与团队主页 —— 增加团队信息、成员和作业管理导航。
 * 模块:届次与比赛 —— 在后台布局中增加届次、参赛队、名单、比赛、进球导航。
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
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php $this->registerCsrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrap">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'class' => 'navbar-inverse navbar-fixed-top',
        ],
    ]);
    $menuItems = [
        ['label' => '首页', 'url' => ['/site/index']],
    ];
    if (!Yii::$app->user->isGuest && Yii::$app->user->identity->role >= common\models\User::ROLE_ADMIN) {
        $menuItems[] = [
            'label' => '团队管理',
            'items' => [
                ['label' => '团队信息', 'url' => ['/team-info/index']],
                ['label' => '成员管理', 'url' => ['/member/index']],
                ['label' => '作业管理', 'url' => ['/work/index']],
            ],
        ];
        $menuItems[] = ['label' => '球队管理', 'url' => ['/team/index']];
        $menuItems[] = ['label' => '球员管理', 'url' => ['/player/index']];
        $menuItems[] = ['label' => '届次', 'url' => ['/tournament/index']];
        $menuItems[] = ['label' => '参赛队', 'url' => ['/tournament-team/index']];
        $menuItems[] = ['label' => '名单', 'url' => ['/squad/index']];
        $menuItems[] = ['label' => '比赛', 'url' => ['/game/index']];
        $menuItems[] = ['label' => '进球', 'url' => ['/goal/index']];
        $menuItems[] = ['label' => '资讯管理', 'url' => ['/news/index']];
        $menuItems[] = ['label' => '留言管理', 'url' => ['/comment/index']];
    }
    if (Yii::$app->user->isGuest) {
        $menuItems[] = ['label' => '登录', 'url' => ['/site/login']];
    } else {
        $menuItems[] = '<li>'
            . Html::beginForm(['/site/logout'], 'post')
            . Html::submitButton(
                '退出登录 (' . Yii::$app->user->identity->username . ')',
                ['class' => 'btn btn-link logout']
            )
            . Html::endForm()
            . '</li>';
    }
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav navbar-right'],
        'items' => $menuItems,
    ]);
    NavBar::end();
    ?>

    <div class="container">
        <?= Breadcrumbs::widget([
            'homeLink' => ['label' => '首页', 'url' => Yii::$app->homeUrl],
            'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
        ]) ?>
        <?= Alert::widget() ?>
        <?= $content ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p class="pull-left">&copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?></p>

        <p class="pull-right">基于 Yii2 框架构建</p>
    </div>
</footer>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
