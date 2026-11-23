<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 球员姓名显示方式切换（中文名 / 原名）。
 *
 * @var yii\web\View $this
 */

use common\models\Player;
use yii\helpers\Html;
use yii\helpers\Url;

$prefersOriginal = Player::prefersOriginalName();
$returnUrl = Url::current();
?>
<span class="pl-name-lang">
    姓名显示：
    <?php if ($prefersOriginal): ?>
        <?= Html::a('中文名', ['/preference/name-lang', 'lang' => 'zh', 'return' => $returnUrl]) ?>
        · <strong>原名</strong>
    <?php else: ?>
        <strong>中文名</strong>
        · <?= Html::a('原名', ['/preference/name-lang', 'lang' => 'orig', 'return' => $returnUrl]) ?>
    <?php endif ?>
</span>
