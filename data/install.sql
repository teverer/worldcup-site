-- ============================================================================
-- 世界杯数据门户网站 —— 数据库安装脚本
-- Team: 国足世界杯夺冠队
-- 文件:data/install.sql
-- 日期:2026-10-18(第 2 版:表名 match→game 等修正)
--
-- 运行环境:MySQL 8.0.16+(5.7 也能执行,但 CHECK 约束不生效)
-- 用法:mysql -u root -p < data/install.sql
--
-- 本文件包含:
--   一、建库 + 14 张表的建表语句(含字段中文注释,可直接当设计文档的数据库字典)
--   二、基础数据:11 届届次 / 账号 / 团队介绍 / 成员占位
--
-- 关键设计说明:
--   1. 表名用 game 而不是 match:match 是 PHP 8 的保留关键字,
--      按它生成的模型类 `class Match` 无法编译,模块③会被卡死。
--   2. goal.team_id 记录"这球记给哪支球队",乌龙球填受益方。
--      不要再用 player + squad 反推球队,那样乌龙球会算错。
--   3. 点球大战比分单独存 home_penalty_score / away_penalty_score;
--      home_score / away_score 始终是 120 分钟(含加时)的比分。
--   4. 外键策略:CASCADE 只用于"随主体消亡的子记录";
--      team / player / user 一律 RESTRICT,防止误删历史数据。
-- ============================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `worldcup`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `worldcup`;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- 一、建表
-- ---------------------------------------------------------------------------

-- 1. tournament 届次 -----------------------------------------------------------
DROP TABLE IF EXISTS `tournament`;
CREATE TABLE `tournament` (
  `id`           int       NOT NULL AUTO_INCREMENT COMMENT '主键',
  `year`         int       NOT NULL COMMENT '年份(1986~2026)',
  `name`         varchar(100) NOT NULL COMMENT '官方名称,如 2022年卡塔尔世界杯',
  `host_country` varchar(100) DEFAULT NULL COMMENT '东道主,多国写在同一文本,如 韩国/日本',
  `team_count`   int          DEFAULT NULL COMMENT '参赛队数(24/32/48)',
  `format`       varchar(100) DEFAULT NULL COMMENT '赛制说明,如 32队/8组/小组前2晋级',
  `win_points`   tinyint   NOT NULL DEFAULT 3 COMMENT '胜一场积分(1986/1990 两届为 2)',
  `start_date`   date         DEFAULT NULL COMMENT '开幕日期',
  `end_date`     date         DEFAULT NULL COMMENT '闭幕日期',
  `create_time`  datetime  NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='届次';

-- 2. team 球队 / 国家队 --------------------------------------------------------
DROP TABLE IF EXISTS `team`;
CREATE TABLE `team` (
  `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name`        varchar(50)  NOT NULL COMMENT '队名/国名',
  `code`        varchar(10)  DEFAULT NULL COMMENT 'FIFA 三字码,如 BRA/GER/ARG',
  `continent`   varchar(20)  DEFAULT NULL COMMENT '所属大洲',
  `flag`        varchar(255) DEFAULT NULL COMMENT '国旗图片相对路径(文件本体不入库)',
  `logo`        varchar(255) DEFAULT NULL COMMENT '队徽图片相对路径',
  `intro`       text         COMMENT '球队简介',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_continent` (`continent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='球队/国家队';

-- 3. player 球员 ---------------------------------------------------------------
DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name`        varchar(50)  NOT NULL COMMENT '姓名(原名)',
  `name_zh`     varchar(50)  DEFAULT NULL COMMENT '中文名(可空;空则展示原名)',
  `birth_date`  date         DEFAULT NULL COMMENT '出生日期',
  `nationality` varchar(50)  DEFAULT NULL COMMENT '国籍(仅展示;代表哪队以 squad 为准)',
  `position`    varchar(20)  DEFAULT NULL COMMENT '主要位置',
  `photo`       varchar(255) DEFAULT NULL COMMENT '照片相对路径',
  `intro`       text         COMMENT '简介',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_nationality` (`nationality`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='球员';

-- 4. user 用户(普通用户 + 管理员共用) -----------------------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username`    varchar(50)  NOT NULL COMMENT '登录名',
  `password`    varchar(255) NOT NULL COMMENT '密码(PHP password_hash 加密,勿用 md5)',
  `nickname`    varchar(50)  DEFAULT NULL COMMENT '昵称(留言显示用)',
  `avatar`      varchar(255) DEFAULT NULL COMMENT '头像相对路径',
  `role`        tinyint      NOT NULL DEFAULT 0 COMMENT '角色 0普通用户 1管理员 2超管',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  CONSTRAINT `chk_user_role` CHECK (`role` IN (0, 1, 2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户(含管理员)';

-- 5. tournament_team 参赛队伍 --------------------------------------------------
DROP TABLE IF EXISTS `tournament_team`;
CREATE TABLE `tournament_team` (
  `id`            int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `tournament_id` int          NOT NULL COMMENT '届次 → tournament.id',
  `team_id`       int          NOT NULL COMMENT '球队 → team.id',
  `group_name`    varchar(10)  DEFAULT NULL COMMENT '该届所在小组(视届次 A~F / A~H / A~L)',
  `coach`         varchar(50)  DEFAULT NULL COMMENT '该届主教练',
  `fifa_rank`     int          DEFAULT NULL COMMENT '参赛时 FIFA 排名',
  `final_rank`    int          DEFAULT NULL COMMENT '最终名次',
  `create_time`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tournament_team` (`tournament_id`, `team_id`),
  KEY `idx_team` (`team_id`),
  CONSTRAINT `fk_tournament_team_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tournament_team_team`       FOREIGN KEY (`team_id`)       REFERENCES `team` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='参赛队伍(某届某队的小组/教练/排名)';

-- 6. squad 参赛名单 ------------------------------------------------------------
DROP TABLE IF EXISTS `squad`;
CREATE TABLE `squad` (
  `id`            int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `tournament_id` int          NOT NULL COMMENT '届次 → tournament.id',
  `team_id`       int          NOT NULL COMMENT '球队 → team.id',
  `player_id`     int          NOT NULL COMMENT '球员 → player.id',
  `shirt_number`  int          DEFAULT NULL COMMENT '该届球衣号(每届可变)',
  `position`      varchar(20)  DEFAULT NULL COMMENT '该届位置(可空,默认取 player.position)',
  `create_time`   datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_squad_player` (`tournament_id`, `player_id`),
  UNIQUE KEY `uk_squad_shirt` (`tournament_id`, `team_id`, `shirt_number`),
  KEY `idx_team` (`team_id`),
  CONSTRAINT `fk_squad_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_squad_team`       FOREIGN KEY (`team_id`)       REFERENCES `team` (`id`),
  CONSTRAINT `fk_squad_player`     FOREIGN KEY (`player_id`)     REFERENCES `player` (`id`),
  CONSTRAINT `chk_squad_shirt` CHECK (`shirt_number` IS NULL OR `shirt_number` BETWEEN 1 AND 99)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='参赛名单(某球员在某届某队的球衣号)';

-- 7. game 比赛(原 match:match 是 PHP 8 保留字,无法生成模型类) -----------------
-- 兼容第 1 版脚本:若之前导入过,旧的 match 表需要一并清掉
DROP TABLE IF EXISTS `match`;
DROP TABLE IF EXISTS `game`;
CREATE TABLE `game` (
  `id`                 int         NOT NULL AUTO_INCREMENT COMMENT '主键',
  `tournament_id`      int         NOT NULL COMMENT '届次 → tournament.id',
  `home_team_id`       int         NOT NULL COMMENT '主队 → team.id',
  `away_team_id`       int         NOT NULL COMMENT '客队 → team.id',
  `home_score`         int         DEFAULT NULL COMMENT '主队比分(120分钟含加时,不含点球大战)',
  `away_score`         int         DEFAULT NULL COMMENT '客队比分(120分钟含加时,不含点球大战)',
  `home_penalty_score` tinyint unsigned DEFAULT NULL COMMENT '点球大战主队比分(无点球大战为 NULL)',
  `away_penalty_score` tinyint unsigned DEFAULT NULL COMMENT '点球大战客队比分(无点球大战为 NULL)',
  `match_time`         datetime    DEFAULT NULL COMMENT '比赛时间',
  `stage`              varchar(20) NOT NULL COMMENT '阶段(小组赛/1/16决赛/1/8决赛/1/4决赛/半决赛/三四名决赛/决赛)',
  `venue`              varchar(50) DEFAULT NULL COMMENT '比赛场地文本,如 卢塞尔球场',
  `status`             tinyint     NOT NULL DEFAULT 0 COMMENT '状态 0未赛 1进行中 2已结束',
  `create_time`        datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_tournament` (`tournament_id`),
  KEY `idx_home_team` (`home_team_id`),
  KEY `idx_away_team` (`away_team_id`),
  KEY `idx_game_time` (`match_time`),
  CONSTRAINT `fk_game_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournament` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_game_home_team`  FOREIGN KEY (`home_team_id`)  REFERENCES `team` (`id`),
  CONSTRAINT `fk_game_away_team`  FOREIGN KEY (`away_team_id`)  REFERENCES `team` (`id`),
  CONSTRAINT `chk_game_teams_diff` CHECK (`home_team_id` <> `away_team_id`),
  CONSTRAINT `chk_game_scores_nonnegative`
    CHECK ((`home_score` IS NULL OR `home_score` >= 0) AND (`away_score` IS NULL OR `away_score` >= 0)),
  CONSTRAINT `chk_game_status` CHECK (`status` IN (0, 1, 2)),
  CONSTRAINT `chk_game_finished_score`
    CHECK (`status` <> 2 OR (`home_score` IS NOT NULL AND `away_score` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='比赛';

-- 8. goal 进球记录 -------------------------------------------------------------
DROP TABLE IF EXISTS `goal`;
CREATE TABLE `goal` (
  `id`               int      NOT NULL AUTO_INCREMENT COMMENT '主键',
  `game_id`          int      NOT NULL COMMENT '比赛 → game.id',
  `team_id`          int      NOT NULL COMMENT '进球归属球队(乌龙球填受益方)',
  `player_id`        int      NOT NULL COMMENT '进球球员(乌龙球填防守方球员)',
  `assist_player_id` int      DEFAULT NULL COMMENT '助攻球员',
  `minute`           int      DEFAULT NULL COMMENT '进球分钟',
  `is_penalty`       tinyint  NOT NULL DEFAULT 0 COMMENT '是否点球 0否 1是',
  `is_own_goal`      tinyint  NOT NULL DEFAULT 0 COMMENT '是否乌龙 0否 1是',
  `is_shootout`      tinyint  NOT NULL DEFAULT 0 COMMENT '是否点球大战进球 0否 1是(统计时排除)',
  `create_time`      datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_game` (`game_id`),
  KEY `idx_team` (`team_id`),
  KEY `idx_player` (`player_id`),
  KEY `idx_assist` (`assist_player_id`),
  CONSTRAINT `fk_goal_game`   FOREIGN KEY (`game_id`)          REFERENCES `game` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_goal_team`   FOREIGN KEY (`team_id`)          REFERENCES `team` (`id`),
  CONSTRAINT `fk_goal_player` FOREIGN KEY (`player_id`)        REFERENCES `player` (`id`),
  CONSTRAINT `fk_goal_assist` FOREIGN KEY (`assist_player_id`) REFERENCES `player` (`id`),
  CONSTRAINT `chk_goal_minute` CHECK (`minute` IS NULL OR `minute` BETWEEN 1 AND 130)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='进球记录';

-- 9. comment 留言(多态挂载) ----------------------------------------------------
DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `user_id`     int          NOT NULL COMMENT '用户 → user.id(留言需登录)',
  `content`     text         NOT NULL COMMENT '留言内容',
  `target_type` varchar(20)  NOT NULL COMMENT '挂载实体 team/player/game/news/tournament/site',
  `target_id`   int          NOT NULL COMMENT '挂载实体 id',
  `parent_id`   int          DEFAULT NULL COMMENT '父留言 id,NULL 表示顶层留言',
  `status`      tinyint      NOT NULL DEFAULT 0 COMMENT '状态 0正常 1已删除',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_type`, `target_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_comment_user`   FOREIGN KEY (`user_id`)   REFERENCES `user` (`id`),
  CONSTRAINT `fk_comment_parent` FOREIGN KEY (`parent_id`) REFERENCES `comment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_comment_target_type`
    CHECK (`target_type` IN ('team', 'player', 'game', 'news', 'tournament', 'site')),
  CONSTRAINT `chk_comment_status` CHECK (`status` IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留言(通过 target_type + target_id 挂载到任意实体)';

-- 10. comment_like 留言点赞 ----------------------------------------------------
DROP TABLE IF EXISTS `comment_like`;
CREATE TABLE `comment_like` (
  `id`          int      NOT NULL AUTO_INCREMENT COMMENT '主键',
  `comment_id`  int      NOT NULL COMMENT '留言 → comment.id',
  `user_id`     int      NOT NULL COMMENT '用户 → user.id',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '点赞时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_comment_user` (`comment_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_comment_like_comment` FOREIGN KEY (`comment_id`) REFERENCES `comment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_like_user`    FOREIGN KEY (`user_id`)    REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='留言点赞(唯一索引防重复点赞)';

-- 11. news 资讯 / 公告 --------------------------------------------------------
DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id`           int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`        varchar(100) NOT NULL COMMENT '标题',
  `content`      text         COMMENT '正文',
  `author`       varchar(50)  DEFAULT NULL COMMENT '作者',
  `cover`        varchar(255) DEFAULT NULL COMMENT '封面图相对路径',
  `status`       tinyint      NOT NULL DEFAULT 0 COMMENT '状态 0草稿 1发布',
  `publish_time` datetime     DEFAULT NULL COMMENT '发布时间',
  `create_time`  datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_publish_time` (`status`, `publish_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资讯/公告';

-- 12. team_info 团队介绍(单例表,全站一条) --------------------------------------
DROP TABLE IF EXISTS `team_info`;
CREATE TABLE `team_info` (
  `id`          tinyint      NOT NULL DEFAULT 1 COMMENT '固定为 1(单例)',
  `name`        varchar(50)  NOT NULL COMMENT '团队名',
  `logo`        varchar(255) DEFAULT NULL COMMENT '团队 Logo 相对路径',
  `slogan`      varchar(100) DEFAULT NULL COMMENT '团队口号',
  `intro`       text         COMMENT '团队介绍',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_team_info_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='团队介绍(单例,后台可编辑)';

-- 13. member 团队成员 ----------------------------------------------------------
DROP TABLE IF EXISTS `member`;
CREATE TABLE `member` (
  `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `name`        varchar(50)  NOT NULL COMMENT '姓名',
  `student_id`  varchar(20)  DEFAULT NULL COMMENT '学号',
  `role`        varchar(20)  DEFAULT NULL COMMENT '组内身份(组长/组员)',
  `duty`        varchar(100) DEFAULT NULL COMMENT '负责模块',
  `avatar`      varchar(255) DEFAULT NULL COMMENT '照片相对路径',
  `intro`       text         COMMENT '个人简介',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='团队成员(个人展示页)';

-- 14. work 作业文件 ------------------------------------------------------------
DROP TABLE IF EXISTS `work`;
CREATE TABLE `work` (
  `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`       varchar(100) NOT NULL COMMENT '文件名/标题',
  `type`        varchar(20)  DEFAULT '团队' COMMENT '类型(团队/个人)',
  `file_path`   varchar(255) NOT NULL COMMENT '文件相对路径,指向 data/team 或 data/personal',
  `description` varchar(255) DEFAULT NULL COMMENT '说明',
  `create_time` datetime     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '上传时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='作业文件(存元数据,文件本体放磁盘)';

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- 二、基础数据
-- ---------------------------------------------------------------------------

-- 2.1 账号 ---------------------------------------------------------------------
-- 两个账号的密码明文都是 admin123(演示用,答辩前自行决定是否改)
-- 改密码:php -r "echo password_hash('新密码', PASSWORD_DEFAULT), PHP_EOL;"
INSERT INTO `user` (`username`, `password`, `nickname`, `role`) VALUES
('admin', '$2y$10$sz2p.DcZb6aK2h3qV2JydegXHfUoqKdpxoyvjccrLPrZhWN52gl1y', '管理员',   1),
('demo',  '$2y$10$sz2p.DcZb6aK2h3qV2JydegXHfUoqKdpxoyvjccrLPrZhWN52gl1y', '演示用户', 0);

-- 2.2 团队介绍(单例,id 固定为 1,插入时不写 id) ---------------------------------
INSERT INTO `team_info` (`name`, `logo`, `slogan`, `intro`) VALUES
('国足世界杯夺冠队', NULL, '用数据看懂世界杯',
 '我们是一个四人小组。本次课程设计以世界杯为主题,搭建一个集球队、球员、比赛数据展示与社区留言于一体的数据门户网站。');

-- 2.3 团队成员 -----------------------------------------------------------------
-- 占位数据:等队友信息齐了直接改这几行,或让模块①的人在后台上传维护
-- 队长自己那一条记得把 role 改成「组长」
INSERT INTO `member` (`name`, `student_id`, `role`, `duty`, `avatar`, `intro`) VALUES
('李行阳', '2411353', '组长', '模块③ 届次与比赛 + 需求文档', NULL, NULL),
('石子川', '2411966', '组员', '模块④ 留言与资讯 + 用户手册/实现文档', NULL, NULL),
('高家铭', '2412267', '组员', '模块② 球队与球员 + 设计文档', NULL, NULL),
('李佳婕', '2413745', '组员', '模块① 框架与团队主页 + 部署文档/项目展示', NULL, NULL);

-- 2.4 届次(1986~2026 共 11 届) ---------------------------------------------------
-- win_points:1986/1990 两届胜一场 2 分,1994 年起改为 3 分
INSERT INTO `tournament` (`year`, `name`, `host_country`, `team_count`, `format`, `win_points`, `start_date`, `end_date`) VALUES
(1986, '1986年墨西哥世界杯', '墨西哥',             24, '24队/6组/小组前2+4个最佳第3晋级', 2, '1986-05-31', '1986-06-29'),
(1990, '1990年意大利世界杯', '意大利',             24, '24队/6组/小组前2+4个最佳第3晋级', 2, '1990-06-08', '1990-07-08'),
(1994, '1994年美国世界杯',   '美国',               24, '24队/6组/小组前2+4个最佳第3晋级', 3, '1994-06-17', '1994-07-17'),
(1998, '1998年法国世界杯',   '法国',               32, '32队/8组/小组前2晋级',           3, '1998-06-10', '1998-07-12'),
(2002, '2002年韩日世界杯',   '韩国/日本',          32, '32队/8组/小组前2晋级',           3, '2002-05-31', '2002-06-30'),
(2006, '2006年德国世界杯',   '德国',               32, '32队/8组/小组前2晋级',           3, '2006-06-09', '2006-07-09'),
(2010, '2010年南非世界杯',   '南非',               32, '32队/8组/小组前2晋级',           3, '2010-06-11', '2010-07-11'),
(2014, '2014年巴西世界杯',   '巴西',               32, '32队/8组/小组前2晋级',           3, '2014-06-12', '2014-07-13'),
(2018, '2018年俄罗斯世界杯', '俄罗斯',             32, '32队/8组/小组前2晋级',           3, '2018-06-14', '2018-07-15'),
(2022, '2022年卡塔尔世界杯', '卡塔尔',             32, '32队/8组/小组前2晋级',           3, '2022-11-20', '2022-12-18'),
(2026, '2026年美加墨世界杯', '美国/加拿大/墨西哥', 48, '48队/12组/小组前2+8个最佳第3晋级', 3, '2026-06-11', '2026-07-19');

-- ---------------------------------------------------------------------------
-- 说明:球队 / 球员 / 比赛 / 进球等演示数据不放在本文件里。
-- 本文件只负责「把库建起来并保证能跑」;演示数据后续单独做 data/demo_data.sql。
-- ---------------------------------------------------------------------------
