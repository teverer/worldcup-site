-- Team: 国足世界杯夺冠队
-- Coding by 李行阳 2411353, 20261124
-- 模块:留言与资讯 —— 留言目标增加 site(全站留言簿)
-- 可重复执行:先按约束名删除旧 CHECK,再按新枚举重建。

SET NAMES utf8mb4;

ALTER TABLE `comment`
    MODIFY `target_type` varchar(20) NOT NULL
        COMMENT '挂载实体 team/player/game/news/tournament/site';

SET @has_check := (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'comment'
      AND constraint_name = 'chk_comment_target_type'
      AND constraint_type = 'CHECK'
);
SET @drop_check := IF(
    @has_check > 0,
    'ALTER TABLE `comment` DROP CHECK `chk_comment_target_type`',
    'SELECT 1'
);
PREPARE stmt FROM @drop_check;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `comment`
    ADD CONSTRAINT `chk_comment_target_type`
    CHECK (`target_type` IN ('team', 'player', 'game', 'news', 'tournament', 'site'));
