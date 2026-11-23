<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261123
 * 模块:球队与球员 —— 访客显示偏好（球员中文名 / 原名）。
 */

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

class PreferenceController extends Controller
{
    /**
     * 切换球员姓名显示语言,写 cookie 后回到来源页。
     *
     * @param string $lang zh|orig
     * @param string|null $return 站内相对地址
     * @return \yii\web\Response
     */
    public function actionNameLang($lang = 'zh', $return = null)
    {
        $lang = $lang === 'orig' ? 'orig' : 'zh';

        // 用原生 setcookie 而不是 Yii 的 Cookie 组件:
        // Yii 会默认对 cookie 值做签名,前端 charts.js 读不到原文,无法同步图表姓名。
        setcookie('wc_player_name', $lang, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        $back = Yii::$app->homeUrl;
        if (is_string($return) && $return !== '' && $return[0] === '/'
            && strpos($return, '//') !== 0 && strpos($return, '\\') === false) {
            $back = $return;
        }

        return $this->redirect($back);
    }
}
