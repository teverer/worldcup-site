-- Team: 国足世界杯夺冠队
-- Coding by 李行阳 2411353, 20261123
-- 模块:球队与球员 —— 球员中文名（第一期：每届最佳射手 + 1986/2022/2026 射手榜前十）
--
-- 数据来源与口径:
--   译名参考中文维基百科对应条目、FIFA 中文官网与央视/新华社赛事报道中的通用译名，
--   人工整理；未收录的球员保持原名，不自行音译、不编造。
--   一人多名时取一个“最常见中文名”，不追求官方全名。
--
-- 用法:
--   先导入 data/install.sql 与两份 real_worldcup 数据，再导入本文件：
--     mysql --default-character-set=utf8mb4 -u root -p worldcup < data/player_name_zh.sql
--   本文件可重复执行：列不存在才新增；只补 name_zh 为空的行，不覆盖人工修订。
--
-- 定位方式:
--   用 name + nationality + birth_date 定位，不用自增 id（干净重建后 id 会漂移）。

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 1. 老库补列（information_schema 判断，MySQL 8 不支持 ADD COLUMN IF NOT EXISTS）
SET @has_name_zh := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'player'
    AND COLUMN_NAME = 'name_zh'
);
SET @ddl := IF(
  @has_name_zh = 0,
  'ALTER TABLE `player` ADD COLUMN `name_zh` varchar(50) DEFAULT NULL COMMENT ''中文名(可空;空则展示原名)'' AFTER `name`',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

START TRANSACTION;

-- 2. 每届最佳射手（含并列第一名）--------------------------------------------
UPDATE `player` SET `name_zh` = '姆巴佩'
WHERE `name` = 'Kylian MBAPPE' AND `nationality` = '法国' AND `birth_date` = '1998-12-20' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '凯恩'
WHERE `name` = 'Harry KANE' AND `nationality` = '英格兰' AND `birth_date` = '1993-07-28' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '哈梅斯·罗德里格斯'
WHERE `name` = 'James RODRIGUEZ' AND `nationality` = '哥伦比亚' AND `birth_date` = '1991-07-12' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '比利亚'
WHERE `name` = 'David VILLA' AND `nationality` = '西班牙' AND `birth_date` = '1981-12-03' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '克洛泽'
WHERE `name` = 'Miroslav KLOSE' AND `nationality` = '德国' AND `birth_date` = '1978-06-09' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '罗纳尔多'
WHERE `name` = 'RONALDO' AND `nationality` = '巴西' AND `birth_date` = '1976-09-22' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '苏克'
WHERE `name` = 'Davor SUKER' AND `nationality` = '克罗地亚' AND `birth_date` = '1968-01-01' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '斯托伊奇科夫'
WHERE `name` = 'Hristo STOICHKOV' AND `nationality` = '保加利亚' AND `birth_date` = '1966-02-08' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '斯基拉奇'
WHERE `name` = 'Salvatore SCHILLACI' AND `nationality` = '意大利' AND `birth_date` = '1964-12-01' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '莱因克尔'
WHERE `name` = 'Gary LINEKER' AND `nationality` = '英格兰' AND `birth_date` = '1960-11-30' AND `name_zh` IS NULL;

-- 3. 1986 射手榜前十（默认届次页最先看到的一张图）----------------------------
UPDATE `player` SET `name_zh` = '卡雷卡'
WHERE `name` = 'CARECA' AND `nationality` = '巴西' AND `birth_date` = '1960-10-05' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '马拉多纳'
WHERE `name` = 'Diego MARADONA' AND `nationality` = '阿根廷' AND `birth_date` = '1960-10-30' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '布特拉格诺'
WHERE `name` = 'Emilio BUTRAGUENO' AND `nationality` = '西班牙' AND `birth_date` = '1963-07-22' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '阿尔托贝利'
WHERE `name` = 'Alessandro ALTOBELLI' AND `nationality` = '意大利' AND `birth_date` = '1955-11-28' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '别拉诺夫'
WHERE `name` = 'Igor BELANOV' AND `nationality` = '苏联' AND `birth_date` = '1960-09-25' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '巴尔达诺'
WHERE `name` = 'Jorge VALDANO' AND `nationality` = '阿根廷' AND `birth_date` = '1955-10-04' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '埃尔克耶尔·拉森'
WHERE `name` = 'Preben ELKJAER LARSEN' AND `nationality` = '丹麦' AND `birth_date` = '1957-09-11' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '瑟勒芒斯'
WHERE `name` = 'Jan CEULEMANS' AND `nationality` = '比利时' AND `birth_date` = '1957-02-28' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '奥尔森'
WHERE `name` = 'Jesper OLSEN' AND `nationality` = '丹麦' AND `birth_date` = '1961-03-20' AND `name_zh` IS NULL;

-- 4. 2026 射手榜前十 ---------------------------------------------------------
UPDATE `player` SET `name_zh` = '梅西'
WHERE `name` = 'Lionel MESSI' AND `nationality` = '阿根廷' AND `birth_date` = '1987-06-24' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '哈兰德'
WHERE `name` = 'Erling Haaland' AND `nationality` = '挪威' AND `birth_date` IS NULL AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '贝林厄姆'
WHERE `name` = 'Jude BELLINGHAM' AND `nationality` = '英格兰' AND `birth_date` = '2003-06-29' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '登贝莱'
WHERE `name` = 'Ousmane DEMBELE' AND `nationality` = '法国' AND `birth_date` = '1997-05-15' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '奥亚萨瓦尔'
WHERE `name` = 'Mikel Oyarzabal' AND `nationality` = '西班牙' AND `birth_date` IS NULL AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '萨尔'
WHERE `name` = 'Ismaila SARR' AND `nationality` = '塞内加尔' AND `birth_date` = '1998-02-25' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '基尼奥内斯'
WHERE `name` = 'Julián Quiñones' AND `nationality` = '墨西哥' AND `birth_date` IS NULL AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '维尼修斯'
WHERE `name` = 'Vinícius Júnior' AND `nationality` = '巴西' AND `birth_date` IS NULL AND `name_zh` IS NULL;

-- 5. 2022 射手榜前十 ---------------------------------------------------------
UPDATE `player` SET `name_zh` = '阿尔瓦雷斯'
WHERE `name` = 'Julian ALVAREZ' AND `nationality` = '阿根廷' AND `birth_date` = '2000-01-31' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '吉鲁'
WHERE `name` = 'Olivier GIROUD' AND `nationality` = '法国' AND `birth_date` = '1986-09-30' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '莫拉塔'
WHERE `name` = 'Alvaro MORATA' AND `nationality` = '西班牙' AND `birth_date` = '1992-10-23' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '萨卡'
WHERE `name` = 'Bukayo SAKA' AND `nationality` = '英格兰' AND `birth_date` = '2001-09-05' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '加克波'
WHERE `name` = 'Cody GAKPO' AND `nationality` = '荷兰' AND `birth_date` = '1999-05-07' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '恩纳·瓦伦西亚'
WHERE `name` = 'Enner VALENCIA' AND `nationality` = '厄瓜多尔' AND `birth_date` = '1989-11-04' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '贡萨洛·拉莫斯'
WHERE `name` = 'GONCALO RAMOS' AND `nationality` = '葡萄牙' AND `birth_date` = '2001-06-20' AND `name_zh` IS NULL;

UPDATE `player` SET `name_zh` = '拉什福德'
WHERE `name` = 'Marcus RASHFORD' AND `nationality` = '英格兰' AND `birth_date` = '1997-10-31' AND `name_zh` IS NULL;

COMMIT;

-- 6. 常见译名修订（把早期全名统一成媒体常用简称；幂等，只改已知旧值）
UPDATE `player` SET `name_zh` = '姆巴佩' WHERE `name_zh` = '基利安·姆巴佩';
UPDATE `player` SET `name_zh` = '凯恩' WHERE `name_zh` = '哈里·凯恩';
UPDATE `player` SET `name_zh` = '比利亚' WHERE `name_zh` = '大卫·比利亚';
UPDATE `player` SET `name_zh` = '克洛泽' WHERE `name_zh` = '米洛斯拉夫·克洛泽';
UPDATE `player` SET `name_zh` = '苏克' WHERE `name_zh` = '达沃尔·苏克';
UPDATE `player` SET `name_zh` = '斯托伊奇科夫' WHERE `name_zh` = '赫里斯托·斯托伊奇科夫';
UPDATE `player` SET `name_zh` = '斯基拉奇' WHERE `name_zh` = '萨尔瓦托雷·斯基拉奇';
UPDATE `player` SET `name_zh` = '莱因克尔' WHERE `name_zh` = '加里·莱因克尔';
UPDATE `player` SET `name_zh` = '布特拉格诺' WHERE `name_zh` = '埃米利奥·布特拉格诺';
UPDATE `player` SET `name_zh` = '阿尔托贝利' WHERE `name_zh` = '亚历山德罗·阿尔托贝利';
UPDATE `player` SET `name_zh` = '别拉诺夫' WHERE `name_zh` = '伊戈尔·别拉诺夫';
UPDATE `player` SET `name_zh` = '巴尔达诺' WHERE `name_zh` = '豪尔赫·巴尔达诺';
UPDATE `player` SET `name_zh` = '埃尔克耶尔·拉森' WHERE `name_zh` = '普雷本·埃尔克耶尔·拉森';
UPDATE `player` SET `name_zh` = '瑟勒芒斯' WHERE `name_zh` = '扬·瑟勒芒斯';
UPDATE `player` SET `name_zh` = '奥尔森' WHERE `name_zh` = '耶斯佩尔·奥尔森';
UPDATE `player` SET `name_zh` = '梅西' WHERE `name_zh` = '利昂内尔·梅西';
UPDATE `player` SET `name_zh` = '哈兰德' WHERE `name_zh` = '埃尔林·哈兰德';
UPDATE `player` SET `name_zh` = '贝林厄姆' WHERE `name_zh` = '裘德·贝林厄姆';
UPDATE `player` SET `name_zh` = '登贝莱' WHERE `name_zh` = '奥斯曼·登贝莱';
UPDATE `player` SET `name_zh` = '奥亚萨瓦尔' WHERE `name_zh` = '米克尔·奥亚萨瓦尔';
UPDATE `player` SET `name_zh` = '萨尔' WHERE `name_zh` = '伊斯梅拉·萨尔';
UPDATE `player` SET `name_zh` = '基尼奥内斯' WHERE `name_zh` = '胡利安·基尼奥内斯';
UPDATE `player` SET `name_zh` = '维尼修斯' WHERE `name_zh` = '维尼修斯·儒尼奥尔';
UPDATE `player` SET `name_zh` = '阿尔瓦雷斯' WHERE `name_zh` = '胡利安·阿尔瓦雷斯';
UPDATE `player` SET `name_zh` = '吉鲁' WHERE `name_zh` = '奥利维耶·吉鲁';
UPDATE `player` SET `name_zh` = '莫拉塔' WHERE `name_zh` = '阿尔瓦罗·莫拉塔';
UPDATE `player` SET `name_zh` = '萨卡' WHERE `name_zh` = '布卡约·萨卡';
UPDATE `player` SET `name_zh` = '加克波' WHERE `name_zh` = '科迪·加克波';
UPDATE `player` SET `name_zh` = '拉什福德' WHERE `name_zh` = '马库斯·拉什福德';
UPDATE `player` SET `name_zh` = '马拉多纳' WHERE `name_zh` = '迭戈·马拉多纳';

-- 6. 2002 中国国家队（原名为拼音，中文读者最需要这一组）---------------------
UPDATE `player` SET `name_zh` = '杜威' WHERE `name` = 'DU Wei' AND `nationality` = '中国' AND `birth_date` = '1982-02-09' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '范志毅' WHERE `name` = 'FAN Zhiyi' AND `nationality` = '中国' AND `birth_date` = '1970-01-22' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '郝海东' WHERE `name` = 'HAO Haidong' AND `nationality` = '中国' AND `birth_date` = '1970-08-25' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '江津' WHERE `name` = 'JIANG Jin' AND `nationality` = '中国' AND `birth_date` = '1968-10-17' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '李铁' WHERE `name` = 'LI Tie' AND `nationality` = '中国' AND `birth_date` = '1977-05-18' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '李玮锋' WHERE `name` = 'LI Weifeng' AND `nationality` = '中国' AND `birth_date` = '1978-01-26' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '李霄鹏' WHERE `name` = 'LI Xiaopeng' AND `nationality` = '中国' AND `birth_date` = '1976-11-05' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '马明宇' WHERE `name` = 'MA Mingyu' AND `nationality` = '中国' AND `birth_date` = '1972-08-10' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '祁宏' WHERE `name` = 'QI Hong' AND `nationality` = '中国' AND `birth_date` = '1976-06-03' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '曲波' WHERE `name` = 'QU Bo' AND `nationality` = '中国' AND `birth_date` = '1981-07-15' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '邵佳一' WHERE `name` = 'SHAO Jiayi' AND `nationality` = '中国' AND `birth_date` = '1980-04-10' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '宿茂臻' WHERE `name` = 'SU Maozhen' AND `nationality` = '中国' AND `birth_date` = '1972-07-30' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '孙继海' WHERE `name` = 'SUN Jihai' AND `nationality` = '中国' AND `birth_date` = '1977-09-30' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '吴承瑛' WHERE `name` = 'WU Chengying' AND `nationality` = '中国' AND `birth_date` = '1975-04-21' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '徐云龙' WHERE `name` = 'XU Yunlong' AND `nationality` = '中国' AND `birth_date` = '1979-02-17' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '杨晨' WHERE `name` = 'YANG Chen' AND `nationality` = '中国' AND `birth_date` = '1974-01-17' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '杨璞' WHERE `name` = 'YANG Pu' AND `nationality` = '中国' AND `birth_date` = '1978-03-30' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '于根伟' WHERE `name` = 'YU Genwei' AND `nationality` = '中国' AND `birth_date` = '1976-08-19' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '肇俊哲' WHERE `name` = 'ZHAO Junzhe' AND `nationality` = '中国' AND `birth_date` = '1979-04-18' AND `name_zh` IS NULL;

-- 7. 补齐“每届最佳射手（含并列）”中遗漏的 4 人 ----------------------------
UPDATE `player` SET `name_zh` = '萨连科' WHERE `name` = 'Oleg SALENKO' AND `nationality` = '俄罗斯' AND `birth_date` = '1969-10-25' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '穆勒' WHERE `name` = 'Thomas MUELLER' AND `nationality` = '德国' AND `birth_date` = '1989-09-13' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '弗兰' WHERE `name` = 'Diego FORLAN' AND `nationality` = '乌拉圭' AND `birth_date` = '1979-05-19' AND `name_zh` IS NULL;
UPDATE `player` SET `name_zh` = '斯内德' WHERE `name` = 'Wesley SNEIJDER' AND `nationality` = '荷兰' AND `birth_date` = '1984-06-09' AND `name_zh` IS NULL;
