-- Team: 国足世界杯夺冠队
-- Coding by 李行阳 2411353, 20261115
-- 模块:届次与比赛 —— 2022 届演示数据(8 队 / A、B 两组 / 小组赛 / 决赛 / 进球)
--
-- 用法:先导入 data/install.sql,再执行本文件(任选一种):
--   mysql -u root -p worldcup < data/demo_game_data.sql
--   mysql --default-character-set=utf8mb4 -u root -p worldcup < data/demo_game_data.sql
-- 特性:可重复执行;只补充缺失数据,不删除、不覆盖已有记录。
--
-- 统计口径说明:
--   1. 积分榜只统计 stage = '小组赛' 且 status = 2 的比赛,胜场积分取 tournament.win_points。
--   2. 射手榜排除 is_own_goal = 1 与 is_shootout = 1 的进球。
--   3. 各队进球按 goal.team_id 分组,乌龙球记在受益球队,点球大战进球排除。
--   4. 决赛 120 分钟 1:1,点球大战 4:3;点球大战进球单独记录并设置 is_shootout = 1。
--   5. 若库中已存在 2022 届决赛(例如已导入模块②演示数据),本脚本不会重复插入决赛;
--      决赛进球只在比赛双方为巴西 vs 西班牙时补充,不会污染已有决赛。
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @wc_year = (SELECT id FROM tournament WHERE year = 2022 LIMIT 1);
-- 若 2022 届不存在,@wc_year 为 NULL,后续插入会因外键 / NOT NULL 失败并整体回滚。

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1. 球队
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_demo_team;
CREATE TEMPORARY TABLE tmp_demo_team (
    name varchar(50) NOT NULL,
    code varchar(10) NOT NULL,
    continent varchar(20) DEFAULT NULL,
    intro text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_team (name, code, continent, intro) VALUES
('巴西', 'BRA', '南美洲', '五届世界杯冠军，2022 届演示数据 A 组球队。'),
('德国', 'GER', '欧洲', '四届世界杯冠军，2022 届演示数据 A 组球队。'),
('日本', 'JPN', '亚洲', '2022 届演示数据 A 组球队。'),
('摩洛哥', 'MAR', '非洲', '2022 届演示数据 A 组球队。'),
('西班牙', 'ESP', '欧洲', '2010 年世界杯冠军，2022 届演示数据 B 组球队。'),
('克罗地亚', 'CRO', '欧洲', '2018 年世界杯亚军，2022 届演示数据 B 组球队。'),
('英格兰', 'ENG', '欧洲', '1966 年世界杯冠军，2022 届演示数据 B 组球队。'),
('荷兰', 'NED', '欧洲', '2022 届演示数据 B 组球队。');

INSERT INTO team (name, code, continent, intro)
SELECT d.name, d.code, d.continent, d.intro
FROM tmp_demo_team d
WHERE NOT EXISTS (
    SELECT 1 FROM team t
    WHERE t.code = d.code OR t.name = d.name
);

DROP TEMPORARY TABLE IF EXISTS tmp_demo_team_id;
CREATE TEMPORARY TABLE tmp_demo_team_id (
    name varchar(50) NOT NULL,
    team_id int NOT NULL,
    PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_team_id (name, team_id)
SELECT d.name, (
    SELECT t.id
    FROM team t
    WHERE t.name = d.name OR t.code = d.code
    ORDER BY (t.name = d.name) DESC, t.id ASC
    LIMIT 1
)
FROM tmp_demo_team d;

-- ---------------------------------------------------------------------------
-- 2. 球员(每队 5 人,共 40 人)
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_demo_player;
CREATE TEMPORARY TABLE tmp_demo_player (
    name varchar(50) NOT NULL,
    birth_date date DEFAULT NULL,
    nationality varchar(50) NOT NULL,
    position varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_player (name, birth_date, nationality, position) VALUES
('内马尔', '1992-02-05', '巴西', '前锋'),
('维尼修斯', '2000-07-12', '巴西', '前锋'),
('理查利森', '1997-05-10', '巴西', '前锋'),
('卡塞米罗', '1992-02-23', '巴西', '中场'),
('阿利松', '1992-10-02', '巴西', '门将'),
('托马斯·穆勒', '1989-09-13', '德国', '前锋'),
('穆西亚拉', '2003-02-26', '德国', '中场'),
('京多安', '1990-10-24', '德国', '中场'),
('基米希', '1995-02-08', '德国', '中场'),
('诺伊尔', '1986-03-27', '德国', '门将'),
('镰田大地', '1996-08-05', '日本', '中场'),
('三笘薰', '1997-05-20', '日本', '前锋'),
('堂安律', '1998-06-16', '日本', '中场'),
('远藤航', '1993-02-09', '日本', '中场'),
('权田修一', '1989-01-19', '日本', '门将'),
('齐耶赫', '1993-03-19', '摩洛哥', '中场'),
('恩内斯里', '1997-06-01', '摩洛哥', '前锋'),
('阿姆拉巴特', '1996-08-21', '摩洛哥', '中场'),
('阿什拉夫·哈基米', '1998-11-04', '摩洛哥', '后卫'),
('布努', '1991-04-05', '摩洛哥', '门将'),
('佩德里', '2002-11-25', '西班牙', '中场'),
('加维', '2004-08-05', '西班牙', '中场'),
('莫拉塔', '1992-10-23', '西班牙', '前锋'),
('费兰·托雷斯', '2000-02-29', '西班牙', '前锋'),
('乌奈·西蒙', '1997-06-11', '西班牙', '门将'),
('莫德里奇', '1985-09-09', '克罗地亚', '中场'),
('佩里西奇', '1989-02-02', '克罗地亚', '前锋'),
('克拉马里奇', '1991-06-19', '克罗地亚', '前锋'),
('科瓦契奇', '1994-05-06', '克罗地亚', '中场'),
('利瓦科维奇', '1995-01-11', '克罗地亚', '门将'),
('凯恩', '1993-07-28', '英格兰', '前锋'),
('萨卡', '2001-09-05', '英格兰', '前锋'),
('贝林厄姆', '2003-06-29', '英格兰', '中场'),
('福登', '2000-05-28', '英格兰', '中场'),
('皮克福德', '1994-03-07', '英格兰', '门将'),
('德佩', '1994-02-13', '荷兰', '前锋'),
('加克波', '1999-05-07', '荷兰', '前锋'),
('德容', '1997-05-12', '荷兰', '中场'),
('邓弗里斯', '1996-04-18', '荷兰', '后卫'),
('诺珀特', '1994-04-07', '荷兰', '门将');

INSERT INTO player (name, birth_date, nationality, position)
SELECT d.name, d.birth_date, d.nationality, d.position
FROM tmp_demo_player d
WHERE NOT EXISTS (
    SELECT 1 FROM player p
    WHERE p.name = d.name AND p.nationality = d.nationality
);

DROP TEMPORARY TABLE IF EXISTS tmp_demo_player_id;
CREATE TEMPORARY TABLE tmp_demo_player_id (
    name varchar(50) NOT NULL,
    nationality varchar(50) NOT NULL,
    player_id int NOT NULL,
    PRIMARY KEY (name, nationality)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_player_id (name, nationality, player_id)
SELECT d.name, d.nationality, (
    SELECT p.id
    FROM player p
    WHERE p.name = d.name AND p.nationality = d.nationality
    ORDER BY p.id ASC
    LIMIT 1
)
FROM tmp_demo_player d;

-- ---------------------------------------------------------------------------
-- 3. 参赛队伍(A 组 / B 组各 4 队)
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_demo_tournament_team;
CREATE TEMPORARY TABLE tmp_demo_tournament_team (
    team_name varchar(50) NOT NULL,
    group_name varchar(10) NOT NULL,
    coach varchar(50) DEFAULT NULL,
    fifa_rank int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_tournament_team (team_name, group_name, coach, fifa_rank) VALUES
('巴西', 'A', '蒂特', 1),
('德国', 'A', '汉斯·弗利克', 11),
('日本', 'A', '森保一', 24),
('摩洛哥', 'A', '瓦利德·雷格拉吉', 22),
('西班牙', 'B', '路易斯·恩里克', 7),
('克罗地亚', 'B', '兹拉特科·达利奇', 12),
('英格兰', 'B', '加雷斯·索斯盖特', 5),
('荷兰', 'B', '路易斯·范加尔', 8);

INSERT INTO tournament_team (tournament_id, team_id, group_name, coach, fifa_rank)
SELECT @wc_year, t.team_id, d.group_name, d.coach, d.fifa_rank
FROM tmp_demo_tournament_team d
JOIN tmp_demo_team_id t ON t.name = d.team_name
WHERE NOT EXISTS (
    SELECT 1 FROM tournament_team tt
    WHERE tt.tournament_id = @wc_year AND tt.team_id = t.team_id
);

-- ---------------------------------------------------------------------------
-- 4. 参赛名单(每队 5 人)
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_demo_squad;
CREATE TEMPORARY TABLE tmp_demo_squad (
    team_name varchar(50) NOT NULL,
    player_name varchar(50) NOT NULL,
    nationality varchar(50) NOT NULL,
    shirt_number int NOT NULL,
    position varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_squad (team_name, player_name, nationality, shirt_number, position) VALUES
('巴西', '内马尔', '巴西', 10, '前锋'),
('巴西', '维尼修斯', '巴西', 7, '前锋'),
('巴西', '理查利森', '巴西', 9, '前锋'),
('巴西', '卡塞米罗', '巴西', 5, '中场'),
('巴西', '阿利松', '巴西', 1, '门将'),
('德国', '托马斯·穆勒', '德国', 13, '前锋'),
('德国', '穆西亚拉', '德国', 14, '中场'),
('德国', '京多安', '德国', 21, '中场'),
('德国', '基米希', '德国', 6, '中场'),
('德国', '诺伊尔', '德国', 1, '门将'),
('日本', '镰田大地', '日本', 15, '中场'),
('日本', '三笘薰', '日本', 11, '前锋'),
('日本', '堂安律', '日本', 8, '中场'),
('日本', '远藤航', '日本', 6, '中场'),
('日本', '权田修一', '日本', 12, '门将'),
('摩洛哥', '齐耶赫', '摩洛哥', 7, '中场'),
('摩洛哥', '恩内斯里', '摩洛哥', 19, '前锋'),
('摩洛哥', '阿姆拉巴特', '摩洛哥', 4, '中场'),
('摩洛哥', '阿什拉夫·哈基米', '摩洛哥', 2, '后卫'),
('摩洛哥', '布努', '摩洛哥', 1, '门将'),
('西班牙', '佩德里', '西班牙', 8, '中场'),
('西班牙', '加维', '西班牙', 9, '中场'),
('西班牙', '莫拉塔', '西班牙', 7, '前锋'),
('西班牙', '费兰·托雷斯', '西班牙', 11, '前锋'),
('西班牙', '乌奈·西蒙', '西班牙', 23, '门将'),
('克罗地亚', '莫德里奇', '克罗地亚', 10, '中场'),
('克罗地亚', '佩里西奇', '克罗地亚', 4, '前锋'),
('克罗地亚', '克拉马里奇', '克罗地亚', 9, '前锋'),
('克罗地亚', '科瓦契奇', '克罗地亚', 8, '中场'),
('克罗地亚', '利瓦科维奇', '克罗地亚', 1, '门将'),
('英格兰', '凯恩', '英格兰', 9, '前锋'),
('英格兰', '萨卡', '英格兰', 17, '前锋'),
('英格兰', '贝林厄姆', '英格兰', 22, '中场'),
('英格兰', '福登', '英格兰', 20, '中场'),
('英格兰', '皮克福德', '英格兰', 1, '门将'),
('荷兰', '德佩', '荷兰', 10, '前锋'),
('荷兰', '加克波', '荷兰', 11, '前锋'),
('荷兰', '德容', '荷兰', 21, '中场'),
('荷兰', '邓弗里斯', '荷兰', 22, '后卫'),
('荷兰', '诺珀特', '荷兰', 1, '门将');

INSERT INTO squad (tournament_id, team_id, player_id, shirt_number, position)
SELECT @wc_year, t.team_id, p.player_id, d.shirt_number, d.position
FROM tmp_demo_squad d
JOIN tmp_demo_team_id t ON t.name = d.team_name
JOIN tmp_demo_player_id p ON p.name = d.player_name AND p.nationality = d.nationality
WHERE NOT EXISTS (
    SELECT 1 FROM squad s
    WHERE s.tournament_id = @wc_year
      AND (
          s.player_id = p.player_id
          OR (s.team_id = t.team_id AND s.shirt_number = d.shirt_number)
      )
);

-- ---------------------------------------------------------------------------
-- 5. 比赛(12 场小组赛 + 1 场决赛)
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_demo_game;
CREATE TEMPORARY TABLE tmp_demo_game (
    seq int NOT NULL,
    home_name varchar(50) NOT NULL,
    away_name varchar(50) NOT NULL,
    home_score int NOT NULL,
    away_score int NOT NULL,
    home_penalty tinyint unsigned DEFAULT NULL,
    away_penalty tinyint unsigned DEFAULT NULL,
    match_time datetime NOT NULL,
    stage varchar(20) NOT NULL,
    venue varchar(50) DEFAULT NULL,
    home_team_id int DEFAULT NULL,
    away_team_id int DEFAULT NULL,
    PRIMARY KEY (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_game (seq, home_name, away_name, home_score, away_score,
    home_penalty, away_penalty, match_time, stage, venue) VALUES
(1, '巴西', '德国', 2, 1, NULL, NULL, '2022-11-21 18:00:00', '小组赛', '卢塞尔球场'),
(2, '日本', '摩洛哥', 1, 1, NULL, NULL, '2022-11-22 18:00:00', '小组赛', '海湾球场'),
(3, '巴西', '日本', 3, 0, NULL, NULL, '2022-11-25 18:00:00', '小组赛', '卢塞尔球场'),
(4, '德国', '摩洛哥', 2, 2, NULL, NULL, '2022-11-26 18:00:00', '小组赛', '海湾球场'),
(5, '巴西', '摩洛哥', 1, 0, NULL, NULL, '2022-11-29 18:00:00', '小组赛', '卢塞尔球场'),
(6, '德国', '日本', 1, 0, NULL, NULL, '2022-11-30 18:00:00', '小组赛', '海湾球场'),
(7, '西班牙', '克罗地亚', 2, 1, NULL, NULL, '2022-11-21 21:00:00', '小组赛', '教育城球场'),
(8, '英格兰', '荷兰', 1, 1, NULL, NULL, '2022-11-22 21:00:00', '小组赛', '哈里发国际球场'),
(9, '西班牙', '英格兰', 1, 1, NULL, NULL, '2022-11-25 21:00:00', '小组赛', '教育城球场'),
(10, '克罗地亚', '荷兰', 2, 0, NULL, NULL, '2022-11-26 21:00:00', '小组赛', '哈里发国际球场'),
(11, '西班牙', '荷兰', 3, 0, NULL, NULL, '2022-11-29 21:00:00', '小组赛', '教育城球场'),
(12, '克罗地亚', '英格兰', 0, 1, NULL, NULL, '2022-11-30 21:00:00', '小组赛', '哈里发国际球场');

-- 已存在任意 2022 届决赛时不重复插入,避免出现两场决赛。
INSERT INTO tmp_demo_game (seq, home_name, away_name, home_score, away_score,
    home_penalty, away_penalty, match_time, stage, venue)
SELECT 13, '巴西', '西班牙', 1, 1, 4, 3, '2022-12-18 18:00:00', '决赛', '卢塞尔球场'
WHERE NOT EXISTS (
    SELECT 1 FROM game
    WHERE tournament_id = @wc_year AND stage = '决赛'
);

-- MySQL 不允许在同一条语句中多次打开同一张临时表,先分步补上球队 id。
UPDATE tmp_demo_game d
JOIN tmp_demo_team_id h ON h.name = d.home_name
SET d.home_team_id = h.team_id;

UPDATE tmp_demo_game d
JOIN tmp_demo_team_id a ON a.name = d.away_name
SET d.away_team_id = a.team_id;

INSERT INTO game (tournament_id, home_team_id, away_team_id, home_score, away_score,
    home_penalty_score, away_penalty_score, match_time, stage, venue, status)
SELECT @wc_year, d.home_team_id, d.away_team_id, d.home_score, d.away_score,
       d.home_penalty, d.away_penalty, d.match_time, d.stage, d.venue, 2
FROM tmp_demo_game d
WHERE d.home_team_id IS NOT NULL
  AND d.away_team_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM game g
      WHERE g.tournament_id = @wc_year
        AND g.stage = d.stage
        AND g.home_team_id = d.home_team_id
        AND g.away_team_id = d.away_team_id
  );

-- ---------------------------------------------------------------------------
-- 6. 进球(含 1 个乌龙球、1 场点球大战)
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_demo_goal;
CREATE TEMPORARY TABLE tmp_demo_goal (
    seq int NOT NULL,
    home_name varchar(50) NOT NULL,
    away_name varchar(50) NOT NULL,
    stage varchar(20) NOT NULL,
    team_name varchar(50) NOT NULL,
    player_name varchar(50) NOT NULL,
    nationality varchar(50) NOT NULL,
    minute int DEFAULT NULL,
    is_penalty tinyint NOT NULL DEFAULT 0,
    is_own_goal tinyint NOT NULL DEFAULT 0,
    is_shootout tinyint NOT NULL DEFAULT 0,
    home_team_id int DEFAULT NULL,
    away_team_id int DEFAULT NULL,
    team_id int DEFAULT NULL,
    player_id int DEFAULT NULL,
    PRIMARY KEY (seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tmp_demo_goal (seq, home_name, away_name, stage, team_name,
    player_name, nationality, minute, is_penalty, is_own_goal, is_shootout) VALUES
(1, '巴西', '德国', '小组赛', '巴西', '内马尔', '巴西', 12, 0, 0, 0),
(2, '巴西', '德国', '小组赛', '巴西', '维尼修斯', '巴西', 55, 0, 0, 0),
(3, '巴西', '德国', '小组赛', '德国', '穆西亚拉', '德国', 78, 0, 0, 0),
(4, '日本', '摩洛哥', '小组赛', '日本', '镰田大地', '日本', 34, 0, 0, 0),
(5, '日本', '摩洛哥', '小组赛', '摩洛哥', '恩内斯里', '摩洛哥', 67, 0, 0, 0),
(6, '巴西', '日本', '小组赛', '巴西', '维尼修斯', '巴西', 10, 0, 0, 0),
(7, '巴西', '日本', '小组赛', '巴西', '理查利森', '巴西', 44, 0, 0, 0),
(8, '巴西', '日本', '小组赛', '巴西', '内马尔', '巴西', 72, 1, 0, 0),
(9, '德国', '摩洛哥', '小组赛', '德国', '托马斯·穆勒', '德国', 20, 0, 0, 0),
(10, '德国', '摩洛哥', '小组赛', '摩洛哥', '齐耶赫', '摩洛哥', 39, 0, 0, 0),
-- 乌龙球:进球记给受益方德国,球员记为摩洛哥防守球员阿什拉夫·哈基米。
(11, '德国', '摩洛哥', '小组赛', '德国', '阿什拉夫·哈基米', '摩洛哥', 61, 0, 1, 0),
(12, '德国', '摩洛哥', '小组赛', '摩洛哥', '恩内斯里', '摩洛哥', 88, 0, 0, 0),
(13, '巴西', '摩洛哥', '小组赛', '巴西', '卡塞米罗', '巴西', 66, 0, 0, 0),
(14, '德国', '日本', '小组赛', '德国', '穆西亚拉', '德国', 52, 0, 0, 0),
(15, '西班牙', '克罗地亚', '小组赛', '西班牙', '莫拉塔', '西班牙', 23, 0, 0, 0),
(16, '西班牙', '克罗地亚', '小组赛', '克罗地亚', '克拉马里奇', '克罗地亚', 41, 0, 0, 0),
(17, '西班牙', '克罗地亚', '小组赛', '西班牙', '佩德里', '西班牙', 76, 0, 0, 0),
(18, '英格兰', '荷兰', '小组赛', '英格兰', '凯恩', '英格兰', 35, 1, 0, 0),
(19, '英格兰', '荷兰', '小组赛', '荷兰', '加克波', '荷兰', 70, 0, 0, 0),
(20, '西班牙', '英格兰', '小组赛', '西班牙', '费兰·托雷斯', '西班牙', 29, 0, 0, 0),
(21, '西班牙', '英格兰', '小组赛', '英格兰', '萨卡', '英格兰', 63, 0, 0, 0),
(22, '克罗地亚', '荷兰', '小组赛', '克罗地亚', '佩里西奇', '克罗地亚', 50, 0, 0, 0),
(23, '克罗地亚', '荷兰', '小组赛', '克罗地亚', '莫德里奇', '克罗地亚', 82, 0, 0, 0),
(24, '西班牙', '荷兰', '小组赛', '西班牙', '莫拉塔', '西班牙', 15, 0, 0, 0),
(25, '西班牙', '荷兰', '小组赛', '西班牙', '加维', '西班牙', 48, 0, 0, 0),
(26, '西班牙', '荷兰', '小组赛', '西班牙', '莫拉塔', '西班牙', 68, 0, 0, 0),
(27, '克罗地亚', '英格兰', '小组赛', '英格兰', '凯恩', '英格兰', 58, 0, 0, 0),
(28, '巴西', '西班牙', '决赛', '巴西', '内马尔', '巴西', 45, 1, 0, 0),
(29, '巴西', '西班牙', '决赛', '西班牙', '莫拉塔', '西班牙', 79, 0, 0, 0),
-- 点球大战:is_shootout = 1,统计时排除;比分 4:3。
(30, '巴西', '西班牙', '决赛', '巴西', '内马尔', '巴西', NULL, 0, 0, 1),
(31, '巴西', '西班牙', '决赛', '巴西', '维尼修斯', '巴西', NULL, 0, 0, 1),
(32, '巴西', '西班牙', '决赛', '巴西', '理查利森', '巴西', NULL, 0, 0, 1),
(33, '巴西', '西班牙', '决赛', '巴西', '卡塞米罗', '巴西', NULL, 0, 0, 1),
(34, '巴西', '西班牙', '决赛', '西班牙', '莫拉塔', '西班牙', NULL, 0, 0, 1),
(35, '巴西', '西班牙', '决赛', '西班牙', '佩德里', '西班牙', NULL, 0, 0, 1),
(36, '巴西', '西班牙', '决赛', '西班牙', '加维', '西班牙', NULL, 0, 0, 1);

-- 同样先分步解析球队 / 球员 id,避免一条语句多次打开同一张临时表。
UPDATE tmp_demo_goal d
JOIN tmp_demo_team_id h ON h.name = d.home_name
SET d.home_team_id = h.team_id;

UPDATE tmp_demo_goal d
JOIN tmp_demo_team_id a ON a.name = d.away_name
SET d.away_team_id = a.team_id;

UPDATE tmp_demo_goal d
JOIN tmp_demo_team_id gt ON gt.name = d.team_name
SET d.team_id = gt.team_id;

UPDATE tmp_demo_goal d
JOIN tmp_demo_player_id p ON p.name = d.player_name AND p.nationality = d.nationality
SET d.player_id = p.player_id;

INSERT INTO goal (game_id, team_id, player_id, minute, is_penalty, is_own_goal, is_shootout)
SELECT gm.id, d.team_id, d.player_id, d.minute,
       d.is_penalty, d.is_own_goal, d.is_shootout
FROM tmp_demo_goal d
JOIN game gm ON gm.tournament_id = @wc_year
    AND gm.stage = d.stage
    AND gm.home_team_id = d.home_team_id
    AND gm.away_team_id = d.away_team_id
WHERE d.home_team_id IS NOT NULL
  AND d.away_team_id IS NOT NULL
  AND d.team_id IS NOT NULL
  AND d.player_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM goal g
      WHERE g.game_id = gm.id
        AND g.player_id = d.player_id
        AND g.minute <=> d.minute
        AND g.is_penalty = d.is_penalty
        AND g.is_own_goal = d.is_own_goal
        AND g.is_shootout = d.is_shootout
  );

COMMIT;

DROP TEMPORARY TABLE IF EXISTS tmp_demo_goal;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_game;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_squad;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_tournament_team;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_player_id;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_player;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_team_id;
DROP TEMPORARY TABLE IF EXISTS tmp_demo_team;
