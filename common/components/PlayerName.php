<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:球队与球员 —— 姓名元素：同时输出中文名与原名，供前端无刷新切换。
 */

namespace common\components;

use common\models\Player;
use yii\helpers\Html;

class PlayerName
{
    /**
     * 给姓名元素加上两种显示值；前端按 cookie 选择 textContent。
     *
     * @param string|null $name 原名
     * @param string|null $nameZh 中文名
     * @param string|null $nationality 国籍
     * @param array $options Html 属性
     * @return array
     */
    public static function options($name, $nameZh, $nationality = null, array $options = [])
    {
        $options['data-name-orig'] = (string) $name;
        if ((string) $nameZh !== '') {
            $options['data-name-zh'] = (string) $nameZh;
        }
        if (Player::isChinesePlayer($nationality)) {
            $options['data-name-always-zh'] = '1';
        }

        return $options;
    }

    /**
     * 生成一个只包含姓名的 span。
     *
     * @param string|null $name
     * @param string|null $nameZh
     * @param string|null $nationality
     * @param array $options
     * @return string
     */
    public static function span($name, $nameZh, $nationality = null, array $options = [])
    {
        $display = Player::formatName($name, $nameZh, $nationality);
        $options = self::options($name, $nameZh, $nationality, $options);
        $options['class'] = isset($options['class'])
            ? $options['class'] . ' js-player-name'
            : 'js-player-name';

        return Html::tag('span', Html::encode($display), $options);
    }
}
