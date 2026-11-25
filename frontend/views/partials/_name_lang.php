<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:球队与球员 —— 球员姓名显示方式切换（中文名 / 原名）。
 *
 * 无 JS 时走 /preference/name-lang 写 cookie 后回跳；
 * 有 JS 时由 name-lang.js 就地改字、写 cookie，不刷新页面、不改变滚动位置。
 *
 * @var yii\web\View $this
 * @var string|null $returnUrl 切换后回到的站内地址;带 #hash 可回到同一个 tab
 */

use common\models\Player;
use yii\helpers\Html;
use yii\helpers\Url;

$prefersOriginal = Player::prefersOriginalName();
$returnUrl = (isset($returnUrl) && is_string($returnUrl) && $returnUrl !== '')
    ? $returnUrl
    : Url::current();

$cssVersion = @filemtime(Yii::getAlias('@webroot/css/name-lang.css')) ?: '';
$jsVersion = @filemtime(Yii::getAlias('@webroot/js/name-lang.js')) ?: '';
$this->registerCssFile(Url::to('@web/css/name-lang.css') . ($cssVersion !== '' ? '?v=' . $cssVersion : ''));
$this->registerJsFile(
    Url::to('@web/js/name-lang.js') . ($jsVersion !== '' ? '?v=' . $jsVersion : ''),
    ['position' => \yii\web\View::POS_END]
);

$optionUrl = static function ($lang) use ($returnUrl) {
    return Url::to(['/preference/name-lang', 'lang' => $lang, 'return' => $returnUrl]);
};
?>
<span class="name-lang" role="group" aria-label="球员姓名显示方式">
    <a class="name-lang-opt<?= $prefersOriginal ? '' : ' is-active' ?>"
       href="<?= Html::encode($optionUrl('zh')) ?>"
       data-name-lang="zh"
       <?= $prefersOriginal ? '' : 'aria-current="true"' ?>>中文名</a>
    <a class="name-lang-opt<?= $prefersOriginal ? ' is-active' : '' ?>"
       href="<?= Html::encode($optionUrl('orig')) ?>"
       data-name-lang="orig"
       <?= $prefersOriginal ? 'aria-current="true"' : '' ?>>原名</a>
</span>
