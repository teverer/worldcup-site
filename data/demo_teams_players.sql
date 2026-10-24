-- Team: 国足世界杯夺冠队
-- Coding by Codex (AI assistance), 20261021
-- 模块②演示数据：先导入 install.sql，再执行本文件；不删除或覆盖已有资料。
-- 参赛名单和决赛记录仅用于联调，不代表完整的 2022 年世界杯数据集。
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO team (name, code, continent, intro)
SELECT '阿根廷', 'ARG', '南美洲', '阿根廷国家男子足球队，2022 年世界杯冠军。'
WHERE NOT EXISTS (SELECT 1 FROM team WHERE code = 'ARG' OR name = '阿根廷');
INSERT INTO team (name, code, continent, intro)
SELECT '法国', 'FRA', '欧洲', '法国国家男子足球队，2022 年世界杯亚军。'
WHERE NOT EXISTS (SELECT 1 FROM team WHERE code = 'FRA' OR name = '法国');

INSERT INTO player (name, birth_date, nationality, position, intro)
SELECT '利昂内尔·梅西', '1987-06-24', '阿根廷', '前锋', '阿根廷队球员，2022 年世界杯金球奖得主。'
WHERE NOT EXISTS (SELECT 1 FROM player WHERE name = '利昂内尔·梅西' AND birth_date = '1987-06-24');
INSERT INTO player (name, birth_date, nationality, position)
SELECT '基利安·姆巴佩', '1998-12-20', '法国', '前锋'
WHERE NOT EXISTS (SELECT 1 FROM player WHERE name = '基利安·姆巴佩' AND birth_date = '1998-12-20');
INSERT INTO player (name, birth_date, nationality, position)
SELECT '安赫尔·迪马利亚', '1988-02-14', '阿根廷', '前锋'
WHERE NOT EXISTS (SELECT 1 FROM player WHERE name = '安赫尔·迪马利亚' AND birth_date = '1988-02-14');
INSERT INTO player (name, birth_date, nationality, position)
SELECT '马库斯·图拉姆', '1997-08-06', '法国', '前锋'
WHERE NOT EXISTS (SELECT 1 FROM player WHERE name = '马库斯·图拉姆' AND birth_date = '1997-08-06');
INSERT INTO player (name, birth_date, nationality, position)
SELECT '亚历克西斯·麦卡利斯特', '1998-12-24', '阿根廷', '中场'
WHERE NOT EXISTS (SELECT 1 FROM player WHERE name = '亚历克西斯·麦卡利斯特' AND birth_date = '1998-12-24');

SET @wc_year = (SELECT id FROM tournament WHERE year = 2022);
SET @wc_arg = (SELECT id FROM team WHERE code = 'ARG');
SET @wc_fra = (SELECT id FROM team WHERE code = 'FRA');
SET @wc_messi = (SELECT MIN(id) FROM player WHERE name = '利昂内尔·梅西' AND birth_date = '1987-06-24');
SET @wc_mbappe = (SELECT MIN(id) FROM player WHERE name = '基利安·姆巴佩' AND birth_date = '1998-12-20');
SET @wc_di_maria = (SELECT MIN(id) FROM player WHERE name = '安赫尔·迪马利亚' AND birth_date = '1988-02-14');
SET @wc_thuram = (SELECT MIN(id) FROM player WHERE name = '马库斯·图拉姆' AND birth_date = '1997-08-06');
SET @wc_mac = (SELECT MIN(id) FROM player WHERE name = '亚历克西斯·麦卡利斯特' AND birth_date = '1998-12-24');

INSERT INTO tournament_team (tournament_id, team_id, group_name, coach, final_rank)
SELECT @wc_year, @wc_arg, 'C', '利昂内尔·斯卡洛尼', 1
WHERE NOT EXISTS (SELECT 1 FROM tournament_team WHERE tournament_id = @wc_year AND team_id = @wc_arg);
INSERT INTO tournament_team (tournament_id, team_id, group_name, coach, final_rank)
SELECT @wc_year, @wc_fra, 'D', '迪迪埃·德尚', 2
WHERE NOT EXISTS (SELECT 1 FROM tournament_team WHERE tournament_id = @wc_year AND team_id = @wc_fra);

INSERT INTO squad (tournament_id, team_id, player_id, shirt_number)
SELECT @wc_year, @wc_arg, @wc_messi, 10
WHERE NOT EXISTS (SELECT 1 FROM squad WHERE tournament_id = @wc_year AND (player_id = @wc_messi OR (team_id = @wc_arg AND shirt_number = 10)));
INSERT INTO squad (tournament_id, team_id, player_id, shirt_number)
SELECT @wc_year, @wc_arg, @wc_di_maria, 11
WHERE NOT EXISTS (SELECT 1 FROM squad WHERE tournament_id = @wc_year AND (player_id = @wc_di_maria OR (team_id = @wc_arg AND shirt_number = 11)));
INSERT INTO squad (tournament_id, team_id, player_id, shirt_number)
SELECT @wc_year, @wc_arg, @wc_mac, 20
WHERE NOT EXISTS (SELECT 1 FROM squad WHERE tournament_id = @wc_year AND (player_id = @wc_mac OR (team_id = @wc_arg AND shirt_number = 20)));
INSERT INTO squad (tournament_id, team_id, player_id, shirt_number)
SELECT @wc_year, @wc_fra, @wc_mbappe, 10
WHERE NOT EXISTS (SELECT 1 FROM squad WHERE tournament_id = @wc_year AND (player_id = @wc_mbappe OR (team_id = @wc_fra AND shirt_number = 10)));
INSERT INTO squad (tournament_id, team_id, player_id, shirt_number)
SELECT @wc_year, @wc_fra, @wc_thuram, 26
WHERE NOT EXISTS (SELECT 1 FROM squad WHERE tournament_id = @wc_year AND (player_id = @wc_thuram OR (team_id = @wc_fra AND shirt_number = 26)));

-- 只在这场决赛尚不存在时补充整场数据，不给已有比赛拼接不完整的进球。
SET @wc_new_final = NOT EXISTS (SELECT 1 FROM game WHERE tournament_id = @wc_year AND stage = '决赛');
INSERT INTO game (tournament_id, home_team_id, away_team_id, home_score, away_score,
    home_penalty_score, away_penalty_score, match_time, stage, venue, status)
SELECT @wc_year, @wc_arg, @wc_fra, 3, 3, 4, 2, '2022-12-18 18:00:00', '决赛', '卢塞尔球场', 2 WHERE @wc_new_final;
SET @wc_final = LAST_INSERT_ID();
INSERT INTO goal (game_id, team_id, player_id, assist_player_id, minute, is_penalty)
SELECT @wc_final, @wc_arg, @wc_messi, NULL, 23, 1 WHERE @wc_new_final;
INSERT INTO goal (game_id, team_id, player_id, assist_player_id, minute, is_penalty)
SELECT @wc_final, @wc_arg, @wc_di_maria, @wc_mac, 36, 0 WHERE @wc_new_final;
INSERT INTO goal (game_id, team_id, player_id, assist_player_id, minute, is_penalty)
SELECT @wc_final, @wc_fra, @wc_mbappe, NULL, 80, 1 WHERE @wc_new_final;
INSERT INTO goal (game_id, team_id, player_id, assist_player_id, minute, is_penalty)
SELECT @wc_final, @wc_fra, @wc_mbappe, @wc_thuram, 81, 0 WHERE @wc_new_final;
INSERT INTO goal (game_id, team_id, player_id, assist_player_id, minute, is_penalty)
SELECT @wc_final, @wc_arg, @wc_messi, NULL, 108, 0 WHERE @wc_new_final;
INSERT INTO goal (game_id, team_id, player_id, assist_player_id, minute, is_penalty)
SELECT @wc_final, @wc_fra, @wc_mbappe, NULL, 118, 1 WHERE @wc_new_final;
COMMIT;
