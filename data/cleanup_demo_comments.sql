-- 清理联调阶段的 user0 测试留言（软删除，保持记录可追溯）。
-- 幂等：重复执行不会改变已删除状态。
-- 2026-11-24
UPDATE `comment`
JOIN `user` ON `user`.`id` = `comment`.`user_id`
SET `comment`.`status` = 1
WHERE `user`.`username` = 'user0'
  AND `comment`.`status` = 0;
