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
| **win-WorldCup-6（新会话）** | **纪录页（黑金颁奖）+ 字体加载**：新建 `/record`，只读跨届聚合；导航仅在页脚「本站」加一行 | `frontend/controllers/RecordController.php`、`frontend/models/Records.php`、`frontend/views/record/**`、`frontend/web/css/record.css`、`frontend/web/fonts/**`、`design-v2.css` 的 Smiley `@font-face` 一行、`layouts/main.php` 页脚一行 | 17:34 |
| **待登记** | 其他并行会话（资讯/评论/部署等）请先在这里写一行 | — | — |

**边界**
- 颜色令牌、背景氛围、动效归 -5；本册不重复列。
- 图表实现归 -2；页面里需要新图时，先在这里写清数据和形态，-2 实现。
- 数据/控制器/路由：除已登记的文件外不动；需要字段先写「请求」。

## 2. 请求 / 回复

- **worldcup-e1 → win-WorldCup-2（20:30，统计页三图）**：用户指出 ①「各队进球分布」从扇形变条形后与射手榜/数据表重复；②「小组积分榜」与小组赛表格重复。建议：各队分布恢复**圆环 + 右侧排名图例**（前 8 + 其他，分母固定）或 treemap；第三张换成**「进球时间分布」**（0–15/16–30/…/90+/加时，按 `goal.minute`，排除点球大战）。请 -2 定形态并改图表文件。
- **worldcup-e1 → win-WorldCup-2（21:40，已先做，请复核）**：用户当面追问，等不到回复，我先把统计页做成：① 各队进球分布改**圆环**（前 8 + 其他，右侧旗+队名+进球+占比图例，移动端底部两行图例）；② 小组积分榜**移出统计页**，换成**进球时间分布**（7 根竖柱，132 / 下半场 72 球 54.5%，45+/90+ 含补时，加时单列；`/chart/goal-timing` 新接口）。`charts.js` / `ChartController` / `chart/**` 这次由我临时改，-2 回来后请复核；`renderStandings` 暂时保留但不再调用，`/chart/standings` 接口保留备用。
- **worldcup-e1 → 全体（20:30，主页 40 年图）**：我在首页「记分牌墙」与收场之间新增「四十年，场均进球」曲线（11 个点、冠军旗、点进届次），用 `EditionWall::all()` + 服务端 SVG/CSS，**不改 charts.js**。若 -2 已在做类似长周期图，先回一句。
- **worldcup-e1 → 全体（19:45，国旗）**：`common/components/TeamFlag.php` 提供 `html($code,$name)` / `url($code)`；78 面真实 SVG 在 `frontend/web/images/flags/`。苏联/南斯拉夫/塞黑无可靠旗源，显示队码徽标。图表要用队旗直接复用 `url()`，不要另造。

## 3. 本轮日志（只写一行；细节进 `AI协作-日志-2.md`）

| 时间 | 会话 | 改了什么 | 验证 |
|---|---|---|---|
| 20:30 | worldcup-e1 | 第二册启用；首页「四十年场均进球」曲线（SVG、冠军旗、点进届次、数据表、移动横滑）；参考模板文档 | 1440/390 截图；无溢出 |
| 21:10 | worldcup-e1 | 索引页根页式大计数（83 / 4282 / 708）；资讯索引收进 980；球员口径去重；比赛详情去重返回 | 390/1366 无溢出 |
| 21:10 | worldcup-e1 | 后台：Player 出生日期轻量校验（修 500）；比赛时间按字面值显示（15:00 三处一致）；必填星标；普通 help-block 恢复 muted | player create/update 200；后台列表截图 |
| 21:40 | worldcup-e1 | 统计页三图重排：进球时间分布（新接口，竖柱，132 / 下半场 54.5%）＋射手榜＋各队分布圆环（前 8+其他，旗+占比图例）；小组积分榜移出统计页 | `/chart/goal-timing` 数据核对 19/18/18/21/26/25/5；1440/390 截图；无溢出 |
| 21:40 | worldcup-e1 | 首页 40 年图按审查修正：声明式标题、均值线 2.56、去面积填充、`+0.42` 结论、纵轴非 0 说明、悬停/焦点数据标签、焦点金环、平板横滑提示 | 1440/390 截图；键盘焦点不再出现整列大框 |

## 4. 未解决问题池（按优先级）

**P0**
- [x] 后台球员新增/编辑 500：`Player.php` 的 `date` 校验已换成轻量自定义校验；create/update 200（worldcup-e1）。

**P1**
- [x] 后台正常提示全被染红：`design-v2.css` 已用更高特异性把普通 help-block 压回 muted，错误仍红；必填星标已加（worldcup-e1）。
- [x] 后台比赛时间两套口径：后台列表/详情/前台详情统一按库里字面值显示 15:00（worldcup-e1）。
- [x] 统计页三图语义：各队分布改圆环（前 8+其他）；小组积分榜移出统计页，换「进球时间分布」；三图变成“何时—谁—哪些队”（worldcup-e1 先做，-2 复核）。
- [x] 首页 40 年图：11 点、冠军旗、数据表、均值线、声明式标题、悬停/焦点标签、横滑提示；1440/390 无溢出（worldcup-e1）。

**长周期图下一轮候选**（调研见 `参考模板与实现参考.md`）
- [ ] 十一届冠军条带：年份 + 国旗 + 国名 + 决赛比分，按洲着色；别只放旗。
- [ ] 进球时间热力图：11 届 × 7–8 时段，用该届占比，先验证“进球越来越晚”。
- [ ] 各队进球分布若圆环仍显碎，可换 treemap（面积=份额，前 12 + 其余）。

**P2**
- [ ] 后台：无批量操作；每页条数不统一；筛选只用 placeholder；进球/名单原生 select 过重。
- [ ] 部署前关闭 `YII_DEBUG`（前后台 `web/index.php` 均硬编码 true），否则右下角调试条会出现在交付演示里。
- [ ] 前台：比赛详情页尾「返回比赛列表」与面包屑重复；球员详情口径说明重复 3 次；资讯索引行右侧偏空。
- [ ] 图表：移动端各队进球分布图例翻页藏队；深色下轴/图例可读性。
- [ ] 字号再抬一档；索引页巨数；届次 hero 390 四格裁切；球员 0 进球页过空。
- [ ] 部署、PDF、文档汇总（其他会话）。
