-- ============================================================================
-- Team: 国足世界杯夺冠队
-- Coding by 李佳婕 2413745, 20261122
-- 模块:框架与团队主页 —— 团队成员正式分工数据
-- 说明:可重复执行,用于同步团队主页展示所需的分工资料。
-- 用法:mysql -u root -p worldcup < data/module1_team_data.sql
-- ============================================================================

SET NAMES utf8mb4;

UPDATE `member`
SET `duty` = '模块③ 届次与比赛 + 需求文档'
WHERE `student_id` = '2411353';

UPDATE `member`
SET `duty` = '模块④ 留言与资讯 + 用户手册/实现文档'
WHERE `student_id` = '2411966';

UPDATE `member`
SET `duty` = '模块② 球队与球员 + 设计文档'
WHERE `student_id` = '2412267';

UPDATE `member`
SET `duty` = '模块① 框架与团队主页 + 部署文档/项目展示'
WHERE `student_id` = '2413745';
