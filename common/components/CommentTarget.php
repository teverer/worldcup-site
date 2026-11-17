<?php
/**
 * Team: 国足世界杯夺冠队
 * Coding by 石子川 2411966, 20261028
 * 模块:留言与资讯 —— 留言挂载目标的统一类型、校验与路由。
 */

namespace common\components;

use yii\db\Query;

class CommentTarget
{
    const TEAM = 'team';
    const PLAYER = 'player';
    const GAME = 'game';
    const NEWS = 'news';
    const TOURNAMENT = 'tournament';

    /**
     * 留言类型与对应数据库表的唯一映射。
     *
     * @return array
     */
    public static function tables()
    {
        return [
            self::TEAM => 'team',
            self::PLAYER => 'player',
            self::GAME => 'game',
            self::NEWS => 'news',
            self::TOURNAMENT => 'tournament',
        ];
    }

    /**
     * @return string[]
     */
    public static function types()
    {
        return array_keys(self::tables());
    }

    /**
     * @return array
     */
    public static function labels()
    {
        return [
            self::TEAM => '球队',
            self::PLAYER => '球员',
            self::GAME => '比赛',
            self::NEWS => '资讯',
            self::TOURNAMENT => '届次',
        ];
    }

    /**
     * @param mixed $type
     * @return bool
     */
    public static function isSupportedType($type)
    {
        return is_string($type) && isset(self::tables()[$type]);
    }

    /**
     * @param mixed $id
     * @return int|null
     */
    public static function normalizeId($id)
    {
        if (is_int($id) && $id > 0) {
            return $id;
        }
        if (is_string($id) && ctype_digit($id) && (int) $id > 0) {
            return (int) $id;
        }
        return null;
    }

    /**
     * 检查留言目标是否真实存在。
     *
     * @param mixed $type
     * @param mixed $id
     * @return bool
     */
    public static function exists($type, $id)
    {
        if (!self::isSupportedType($type)) {
            return false;
        }

        $targetId = self::normalizeId($id);
        if ($targetId === null) {
            return false;
        }

        return (new Query())
            ->from('{{%' . self::tables()[$type] . '}}')
            ->where(['id' => $targetId])
            ->exists();
    }

    /**
     * @param string $type
     * @return string
     */
    public static function label($type)
    {
        $labels = self::labels();
        return isset($labels[$type]) ? $labels[$type] : '实体';
    }

    /**
     * 返回留言目标对应前台详情页的路由。
     *
     * @param string $type
     * @param int $id
     * @return array|null
     */
    public static function route($type, $id)
    {
        $routes = [
            self::TEAM => '/team/view',
            self::PLAYER => '/player/view',
            self::GAME => '/game/view',
            self::NEWS => '/news/view',
            self::TOURNAMENT => '/tournament/view',
        ];

        return isset($routes[$type]) ? [$routes[$type], 'id' => $id] : null;
    }
}
