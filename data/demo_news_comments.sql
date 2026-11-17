-- Team: 国足世界杯夺冠队
-- Coding by 石子川 2411966, 20261117
-- 模块:留言与资讯演示数据
-- 先导入 install.sql 和 demo_teams_players.sql，再执行本文件。
-- 本脚本可重复执行，不覆盖已有资讯、留言或点赞。

SET NAMES utf8mb4;
START TRANSACTION;

SET @demo_user = (SELECT id FROM user WHERE username = 'demo' LIMIT 1);
SET @admin_user = (SELECT id FROM user WHERE username = 'admin' LIMIT 1);
SET @arg_team = (SELECT id FROM team WHERE code = 'ARG' LIMIT 1);
SET @messi = (
    SELECT MIN(id)
    FROM player
    WHERE name = '利昂内尔·梅西' AND birth_date = '1987-06-24'
);
SET @wc2022 = (SELECT id FROM tournament WHERE year = 2022 LIMIT 1);
SET @final_game = (
    SELECT id
    FROM game
    WHERE tournament_id = @wc2022 AND stage = '决赛'
    ORDER BY id
    LIMIT 1
);

INSERT INTO news (title, content, author, status, publish_time)
SELECT
    '2022 世界杯决赛复盘',
    '<p>阿根廷与法国在卢塞尔球场上演经典决赛。比赛进入点球大战后，阿根廷最终夺冠。</p>',
    '演示用户',
    1,
    '2022-12-19 10:00:00'
WHERE @demo_user IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM news WHERE title = '2022 世界杯决赛复盘'
  );

INSERT INTO news (title, content, author, status)
SELECT
    '2026 世界杯数据观察草稿',
    '<p>这一条是仅后台可见的草稿，用于演示草稿状态。</p>',
    '演示用户',
    0
WHERE @demo_user IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM news WHERE title = '2026 世界杯数据观察草稿'
  );

SET @demo_news = (
    SELECT id FROM news WHERE title = '2022 世界杯决赛复盘' LIMIT 1
);

INSERT INTO comment (user_id, content, target_type, target_id, status)
SELECT
    @demo_user,
    '这场比赛的过程非常精彩，点球大战尤其紧张。',
    'news',
    @demo_news,
    0
WHERE @demo_user IS NOT NULL
  AND @demo_news IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM comment
      WHERE content = '这场比赛的过程非常精彩，点球大战尤其紧张。'
  );

SET @news_root = (
    SELECT id
    FROM comment
    WHERE content = '这场比赛的过程非常精彩，点球大战尤其紧张。'
    ORDER BY id
    LIMIT 1
);

INSERT INTO comment (user_id, content, target_type, target_id, parent_id, status)
SELECT
    @admin_user,
    '决赛中的攻防转换也值得结合比赛数据进一步分析。',
    'news',
    @demo_news,
    @news_root,
    0
WHERE @admin_user IS NOT NULL
  AND @demo_news IS NOT NULL
  AND @news_root IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM comment
      WHERE content = '决赛中的攻防转换也值得结合比赛数据进一步分析。'
  );

INSERT INTO comment (user_id, content, target_type, target_id, status)
SELECT
    @demo_user,
    '阿根廷是世界杯历史上非常成功的球队之一。',
    'team',
    @arg_team,
    0
WHERE @demo_user IS NOT NULL
  AND @arg_team IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM comment
      WHERE content = '阿根廷是世界杯历史上非常成功的球队之一。'
  );

INSERT INTO comment (user_id, content, target_type, target_id, status)
SELECT
    @demo_user,
    '梅西在 2022 年世界杯的表现令人印象深刻。',
    'player',
    @messi,
    0
WHERE @demo_user IS NOT NULL
  AND @messi IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM comment
      WHERE content = '梅西在 2022 年世界杯的表现令人印象深刻。'
  );

INSERT INTO comment (user_id, content, target_type, target_id, status)
SELECT
    @admin_user,
    '这场决赛的比分和进球记录可以在比赛详情页查看。',
    'game',
    @final_game,
    0
WHERE @admin_user IS NOT NULL
  AND @final_game IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM comment
      WHERE content = '这场决赛的比分和进球记录可以在比赛详情页查看。'
  );

INSERT INTO comment (user_id, content, target_type, target_id, status)
SELECT
    @admin_user,
    '2022 年世界杯留下了许多值得回顾的数据。',
    'tournament',
    @wc2022,
    0
WHERE @admin_user IS NOT NULL
  AND @wc2022 IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM comment
      WHERE content = '2022 年世界杯留下了许多值得回顾的数据。'
  );

INSERT IGNORE INTO comment_like (comment_id, user_id)
SELECT @news_root, @demo_user
WHERE @news_root IS NOT NULL AND @demo_user IS NOT NULL;

INSERT IGNORE INTO comment_like (comment_id, user_id)
SELECT @news_root, @admin_user
WHERE @news_root IS NOT NULL AND @admin_user IS NOT NULL;

COMMIT;
