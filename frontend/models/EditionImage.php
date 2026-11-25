<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 李行阳 2411353, 20261125
 * 模块:届次与比赛 —— 届次代表图
 *
 * 每届一张“那一届的画面”：夺冠、经典瞬间或当届最有辨识度的照片。
 * 只在文件存在时返回，缺图的届次保持现有夜场头，不出现空框。
 * 首页只用 320×180 缩略图；届次页用 1920×800 图片（object-fit: cover），
 * 并在右上角标注作者/许可。
 */

namespace frontend\models;

use Yii;
use yii\helpers\Url;

class EditionImage
{
    private const IMAGES = [
        1986 => [
            'hero' => '1986.webp',
            'thumb' => '1986-thumb.webp',
            'focus' => 'center 30%',
            'alt' => '1986 年世界杯决赛后，马拉多纳在阿兹特克球场举起奖杯',
            'credit' => 'El Gráfico / Public domain',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:Maradona_cup_azteca.jpg',
        ],
        1990 => [
            'hero' => '1990.webp',
            'thumb' => '1990-thumb.webp',
            'focus' => 'center 40%',
            'alt' => '1990 年世界杯决赛后，马拉多纳落泪',
            'credit' => 'AFP/Getty / Public domain',
            'credit_url' => 'https://it.wikipedia.org/wiki/File:Maradona_finale_1990.jpg',
        ],
        1994 => [
            'hero' => '1994.webp',
            'thumb' => '1994-thumb.webp',
            'focus' => '65% 0%',
            'alt' => '1994 年世界杯决赛，巴乔的背影与罗马里奥在场上',
            'credit' => 'Getty / Action Images（合成）',
            'credit_url' => 'https://www.theguardian.com/football/2019/jul/12/usa-94-football-world-cup',
        ],
        1998 => [
            'hero' => '1998.webp',
            'thumb' => '1998-thumb.webp',
            'focus' => 'center 40%',
            'alt' => '1998 年世界杯决赛后，法国队队长德尚举起奖杯',
            'credit' => 'Michel Lipchitz/AP',
            'credit_url' => 'https://www.theguardian.com/football/2020/may/14/my-favourite-game-france-v-brazil-1998-world-cup-final',
        ],
        2002 => [
            'hero' => '2002.webp',
            'thumb' => '2002-thumb.webp',
            'focus' => '28% 0%',
            'alt' => '2002 年世界杯决赛，罗纳尔多庆祝进球',
            'credit' => 'David Cannon/Getty Images',
            'credit_url' => 'https://www.theguardian.com/football/2017/jun/29/ronaldo-brazil-world-cup-final-2002-japan',
        ],
        2006 => [
            'hero' => '2006.webp',
            'thumb' => '2006-thumb.webp',
            'focus' => 'center 45%',
            'alt' => '2006 年世界杯决赛，齐达内与意大利球员在场上',
            'credit' => 'David Ruddell / CC BY 2.0',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:Italy_vs_France_2006.jpg',
        ],
        2010 => [
            'hero' => '2010.webp',
            'thumb' => '2010-thumb.webp',
            'focus' => 'center 35%',
            'alt' => '2010 年世界杯决赛后，西班牙队举杯',
            'credit' => 'Christophe Badoux / CC BY-SA 3.0',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:2010_FIFA_World_Cup_Spain_with_cup.JPG',
        ],
        2014 => [
            'hero' => '2014.webp',
            'thumb' => '2014-thumb.webp',
            'focus' => 'center 45%',
            'alt' => '2014 年世界杯决赛后，德国队在马拉卡纳球场庆祝夺冠',
            'credit' => 'Danilo Borges / Portal da Copa / CC BY 3.0',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:Germany_champions_2014_FIFA_World_Cup.jpg',
        ],
        2018 => [
            'hero' => '2018.webp',
            'thumb' => '2018-thumb.webp',
            'focus' => 'center 35%',
            'alt' => '2018 年世界杯后，法国队庆祝夺冠',
            'credit' => 'Russian Presidential Press / CC BY 4.0',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:France_champion_of_the_Football_World_Cup_Russia_2018.jpg',
        ],
        2022 => [
            'hero' => '2022.webp',
            'thumb' => '2022-thumb.webp',
            'focus' => '60% 55%',
            'alt' => '2022 年世界杯决赛，梅西在点球点前主罚',
            'credit' => 'Sebas / CC BY 3.0',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:Argentina_3-3_Francia_-_Copa_Mundial_2022_-_Messi_patea_un_penal.jpg',
        ],
        2026 => [
            'hero' => '2026.webp',
            'thumb' => '2026-thumb.webp',
            'focus' => 'center 35%',
            'alt' => '2026 年世界杯决赛后，西班牙队举杯庆祝',
            'credit' => 'Bryan Berlin / CC BY-SA 4.0',
            'credit_url' => 'https://commons.wikimedia.org/wiki/File:Spain_World_Cup_Winners_Argentina_v_Spain_19_July_2026-1.jpg',
        ],
    ];

    /**
     * @return array{hero_url:string,thumb_url:string,focus:string,alt:string,credit:string,credit_url:string}|null
     */
    public static function forYear(int $year): ?array
    {
        $image = self::IMAGES[$year] ?? null;
        if ($image === null) {
            return null;
        }

        $heroFile = Yii::getAlias('@webroot/images/editions/' . $image['hero']);
        if (!is_file($heroFile)) {
            return null;
        }
        $thumbFile = Yii::getAlias('@webroot/images/editions/' . $image['thumb']);

        // 图片换过之后用文件时间做版本号，避免浏览器继续用旧缓存。
        $heroVersion = @filemtime($heroFile) ?: '';
        $image['hero_url'] = Url::to('@web/images/editions/' . $image['hero'])
            . ($heroVersion !== '' ? '?v=' . $heroVersion : '');
        if (is_file($thumbFile)) {
            $thumbVersion = @filemtime($thumbFile) ?: '';
            $image['thumb_url'] = Url::to('@web/images/editions/' . $image['thumb'])
                . ($thumbVersion !== '' ? '?v=' . $thumbVersion : '');
        } else {
            $image['thumb_url'] = $image['hero_url'];
        }

        return $image;
    }
}
