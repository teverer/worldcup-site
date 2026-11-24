<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261124
 * 模块:界面设计 —— 球队国旗：FIFA 代码到本地区旗文件的映射与输出。
 *
 * 旗文件在 frontend/web/images/flags/，来自 Flagpedia / flagcdn（公共领域旗帜）。
 * 历史球队没有可直接对应的现代国旗（苏联、南斯拉夫、塞黑）时退回队码徽标，
 * 不拿今天的国旗冒充历史；西德 / 捷克斯洛伐克与今日德 / 捷旗帜图案一致，允许复用。
 */

namespace common\components;

use Yii;
use yii\helpers\Html;

class TeamFlag
{
    const BASE_PATH = '/images/flags/';

    /**
     * FIFA 三字码 → ISO 3166-1 alpha-2（英格兰 / 苏格兰 / 威尔士 / 北爱尔兰用 GB 分区码）。
     *
     * @return array<string,string>
     */
    public static function map()
    {
        return [
            'ALG' => 'dz', 'ANG' => 'ao', 'ARG' => 'ar', 'AUS' => 'au', 'AUT' => 'at',
            'BEL' => 'be', 'BIH' => 'ba', 'BOL' => 'bo', 'BRA' => 'br', 'BUL' => 'bg',
            'CAN' => 'ca', 'CHI' => 'cl', 'CHN' => 'cn', 'CIV' => 'ci', 'CMR' => 'cm',
            'COD' => 'cd', 'COL' => 'co', 'CPV' => 'cv', 'CRC' => 'cr', 'CRO' => 'hr',
            'CUW' => 'cw', 'CZE' => 'cz', 'DEN' => 'dk', 'ECU' => 'ec', 'EGY' => 'eg',
            'ENG' => 'gb-eng', 'ESP' => 'es', 'FRA' => 'fr', 'FRG' => 'de', 'GER' => 'de',
            'GHA' => 'gh', 'GRE' => 'gr', 'HAI' => 'ht', 'HON' => 'hn', 'HUN' => 'hu',
            'IRL' => 'ie', 'IRN' => 'ir', 'IRQ' => 'iq', 'ISL' => 'is', 'ITA' => 'it',
            'JAM' => 'jm', 'JOR' => 'jo', 'JPN' => 'jp', 'KOR' => 'kr', 'KSA' => 'sa',
            'MAR' => 'ma', 'MEX' => 'mx', 'NED' => 'nl', 'NGA' => 'ng', 'NIR' => 'gb-nir',
            'NOR' => 'no', 'NZL' => 'nz', 'PAN' => 'pa', 'PAR' => 'py', 'PER' => 'pe',
            'POL' => 'pl', 'POR' => 'pt', 'PRK' => 'kp', 'QAT' => 'qa', 'ROU' => 'ro',
            'RSA' => 'za', 'RUS' => 'ru', 'SCO' => 'gb-sct', 'SEN' => 'sn', 'SRB' => 'rs',
            'SUI' => 'ch', 'SVK' => 'sk', 'SVN' => 'si', 'SWE' => 'se', 'TCH' => 'cz',
            'TOG' => 'tg', 'TRI' => 'tt', 'TUN' => 'tn', 'TUR' => 'tr', 'UAE' => 'ae',
            'UKR' => 'ua', 'URU' => 'uy', 'USA' => 'us', 'UZB' => 'uz', 'WAL' => 'gb-wls',
        ];
    }

    /**
     * @param mixed $code
     * @return string|null
     */
    public static function iso($code)
    {
        $code = strtoupper(trim((string) $code));
        $map = self::map();
        if (!isset($map[$code])) {
            return null;
        }
        $iso = $map[$code];
        $file = Yii::getAlias('@frontend') . '/web' . self::BASE_PATH . $iso . '.svg';

        return is_file($file) ? $iso : null;
    }

    /**
     * 输出一枚国旗；没有可靠旗源时输出队码小徽标（不是空白）。
     *
     * @param mixed $code FIFA 代码
     * @param string $name 队名，用于 img alt（默认装饰性，因为旁边已有队名）
     * @param string $className 追加 class
     * @return string
     */
    public static function html($code, $name = '', $className = '')
    {
        $code = strtoupper(trim((string) $code));
        $classes = trim('team-flag ' . $className);
        $iso = self::iso($code);

        if ($iso === null) {
            return Html::tag(
                'span',
                Html::encode($code !== '' ? $code : '—'),
                ['class' => $classes . ' team-flag--code', 'aria-hidden' => 'true']
            );
        }

        return Html::tag('span', Html::img(
            Yii::getAlias('@web') . self::BASE_PATH . $iso . '.svg',
            ['alt' => '', 'loading' => 'lazy', 'width' => 24, 'height' => 18]
        ), ['class' => $classes, 'aria-hidden' => 'true']);
    }
}
