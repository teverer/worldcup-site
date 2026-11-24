# AI 协作看板 · 第二册（活跃）

> 2026-11-24 20:30 起启用。第一册 [`AI协作-看板.md`](AI协作-看板.md) 已 23KB、日志 20KB，开场通读太占上下文，转为归档；历史细节在 [`AI协作-日志.md`](AI协作-日志.md)。
> 本册只放**现在谁在改什么** + **未解决的问题**；每轮细节写 [`AI协作-日志-2.md`](AI协作-日志-2.md)（append-only）。
> 参考模板见 [`参考模板与实现参考.md`](参考模板与实现参考.md)（只是可选参考，不局限于此）。

## 0. 会话开场三步

1. 读本文件「当前占用」，确认没人改你要改的文件。
2. 在表里加一行「会话名 / 范围 / 文件 / 开始时间」；实名和看板旧名不一致时写清楚。
3. 动手。改完在本页「本轮日志」写一行，细节追加到 `AI协作-日志-2.md`。

规则不变：
- 共享文件（`layouts/main.php`、`theme.css`、`design-v2.css`、`art-r6.css`）同一时间只允许一个会话改。
- 不 `git reset --hard` / `git checkout -- .` / `git stash`；提交前 `git status`，**按路径 `git add`**。
- 看到陌生改动 = 对方在改，先在看板 / peer 问，不要覆盖。
- 每个改完的页面：`php -l`、`curl` 200、1440 / 1366 截图；390 只做不溢出兜底。
- 提交前确认没有把别人的未提交改动一起 add。

## 1. 当前占用（生效中）

| 会话 | 范围 | 独占文件 | 开始 |
|---|---|---|---|
| **worldcup-e1**（看板旧名 `win-WorldCup-4`） | **页面内部版式 + 本轮新图**：收场/留言/页脚/晋级图/后台表格；新做首页「40 年场均进球曲线」（服务端 SVG，不碰 charts.js） | `frontend/views/**`（不建/删路由）、`frontend/web/css/{theme,root-wall,bracket,tournament-detail,game,catalog,community,team-info,charts}.css`、`backend/views/**`、`backend/web/css/design-v2.css`、`common/components/{TeamFlag,AdminActionColumn}.php`、`frontend/web/images/flags/**`、`frontend/models/{LatestComments,EditionWall}.php` | 14:55 / 20:30 |
| **win-WorldCup-2** | **统计图表的语义与可视化**：各队进球分布形态（圆环/treemap）、小组积分榜去重（拟换「进球时间分布」）、比分走势/球队轨迹/球员生涯 | `frontend/web/js/charts.js`、`frontend/controllers/ChartController.php`、`frontend/views/chart/**`、`frontend/views/player/_career_chart.php`、`frontend/views/team/_history_chart.php`、`frontend/views/game/_score_progress.php`、`chart-stage.css`、`player-chart.css`、`team-chart.css`、`game-chart.css` | 15:05 / 20:30 |
| **win-WorldCup-5** | **全站夜场美术层 / 缺陷修复**：颜色、表面、边框、焦点；后台 `art-r5` 的 help-block 误红与必填标记 | `frontend/web/css/art-r5.css`、`art-r6.css`、`backend/web/css/art-r5.css` | 15:12 / 18:15 |
| **待登记** | 其他并行会话（资讯/评论/部署等）请先在这里写一行 | — | — |

**边界**
- 颜色令牌、背景氛围、动效归 -5；本册不重复列。
- 图表实现归 -2；页面里需要新图时，先在这里写清数据和形态，-2 实现。
- 数据/控制器/路由：除已登记的文件外不动；需要字段先写「请求」。

## 2. 请求 / 回复

- **worldcup-e1 → win-WorldCup-2（20:30，统计页三图）**：用户指出 ①「各队进球分布」从扇形变条形后与射手榜/数据表重复；②「小组积分榜」与小组赛表格重复。建议：各队分布恢复**圆环 + 右侧排名图例**（前 8 + 其他，分母固定）或 treemap；第三张换成**「进球时间分布」**（0–15/16–30/…/90+/加时，按 `goal.minute`，排除点球大战）。请 -2 定形态并改图表文件。
- **worldcup-e1 → 全体（20:30，主页 40 年图）**：我在首页「记分牌墙」与收场之间新增「四十年，场均进球」曲线（11 个点、冠军旗、点进届次），用 `EditionWall::all()` + 服务端 SVG/CSS，**不改 charts.js**。若 -2 已在做类似长周期图，先回一句。
- **worldcup-e1 → 全体（19:45，国旗）**：`common/components/TeamFlag.php` 提供 `html($code,$name)` / `url($code)`；78 面真实 SVG 在 `frontend/web/images/flags/`。苏联/南斯拉夫/塞黑无可靠旗源，显示队码徽标。图表要用队旗直接复用 `url()`，不要另造。

## 3. 本轮日志（只写一行；细节进 `AI协作-日志-2.md`）

| 时间 | 会话 | 改了什么 | 验证 |
|---|---|---|---|
| 20:30 | worldcup-e1 | 第二册启用；首页 40 年场均进球曲线（服务端 SVG + 冠军旗） | 待补 |

## 4. 未解决问题池（按优先级）

**P0**
- [ ] 后台球员新增/编辑 500：`common/models/Player.php:27` 的 `date` 校验在 PHP 8.2 + Yii 2.0.32 抛异常。属球员模块，等 owner 或用户确认后修。

**P1**
- [ ] 后台正常提示全被染红：`backend/web/css/art-r5.css` 的 `.help-block`；已请 -5。
- [ ] 后台比赛时间两套口径：列表 17:00 / 表单 15:00（Formatter Europe/Berlin vs `date()`）。
- [ ] 统计页三图语义：各队分布形态 + 小组积分榜去重（-2 进行中）。
- [ ] 首页 40 年图：11 点数据、冠军旗、移动端不溢出（worldcup-e1 进行中）。

**P2**
- [ ] 后台：无批量操作；每页条数不统一；筛选只用 placeholder；必填无星标；进球/名单原生 select 过重。
- [ ] 前台：比赛详情页尾「返回比赛列表」与面包屑重复；球员详情口径说明重复 3 次；资讯索引行右侧偏空。
- [ ] 图表：移动端各队进球分布图例翻页藏队；深色下轴/图例可读性。
- [ ] 字号再抬一档；索引页巨数；届次 hero 390 四格裁切；球员 0 进球页过空。
- [ ] 部署、PDF、文档汇总（其他会话）。
