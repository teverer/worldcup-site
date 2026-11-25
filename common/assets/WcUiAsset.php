<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:界面设计 —— 前后台共用的确认对话框与轻提示。
 *
 * 资源放在 common 下，由 AssetManager 分别发布到前台 / 后台的 web/assets；
 * 只依赖原生 <dialog> / CSS，不引 jQuery 或第三方弹窗库。
 */

namespace common\assets;

use yii\web\AssetBundle;

class WcUiAsset extends AssetBundle
{
    public $sourcePath = '@common/assets/wc-ui';
    public $css = [
        'wc-ui.css',
    ];
    public $js = [
        'wc-ui.js',
    ];
    public $depends = [];
}
