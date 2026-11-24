# AI 协作日志（append-only）

> 每轮完成后在这里追加：时间 / 会话 / 改了什么 / 为什么 / 验证 / 提交。
> 平时不用通读；只在追溯某个改动时按时间或会话检索。

## 2026-11-24

### 14:55 · win-WorldCup-4 · 建立新协作机制
- 新建 `docs/AI协作-看板.md`：只放当前占用、请求、问题池和一行式日志。
- `docs/ai对话.md` 顶部加快照说明，转为历史归档。
- 本会话认领：页面内部版式与字体加载（前台 + 后台），不动颜色令牌 / 氛围动效 / 数据控制器。
- 验证：文档变更，无代码影响。

### 15:35 · win-WorldCup-2 · 图表语义与球员图
- **各队进球分布**：图例与切片都设为不可隐藏（`selectedMode:false`），占比统一以全届总进球为分母；图例改为右侧竖排，直接显示「队名 + 占比」；「其他 N 队」保留真实进球与占比。修复「点图例隐藏一支队后，其余队百分比被重新归一化」的反直觉问题。
- **配色**：球队色从 5 色循环改为 8 级深绿→青→灰蓝，相邻切片不再撞色；金仍只给第一名。
- **球员页新增图**：`frontend/views/player/_career_chart.php` + `player-chart.css` + `charts.js` 的 `initPlayerCareer()`；按届次并排显示进球/助攻；只有一届时降为 220px 高；零进球/零助攻的球员不渲染这一节。
- **文案**：各队进球卡片说明改为「前 7 队单列，其余合并为「其他」；占比以全届总进球为分母，不随图例变化」。
- 验证：`php -l` 通过；`node --check` 通过；390 / 1440 截图；无页面级横向溢出；图例点击不改变占比（ECharts `selectedMode:false`）。
- 文件：`frontend/web/js/charts.js`、`frontend/controllers/ChartController.php`（未改，仅评估）、`frontend/views/chart/_charts.php`、`frontend/views/player/_career_chart.php`（新）、`frontend/views/player/view.php`（一行 include）、`frontend/web/css/player-chart.css`（新）。

### 16:05 · win-WorldCup-4 · 字体加载 + 页面内部版式（本轮收尾）
- **字体（用户点名问题）**：原 1.15MB 得意黑 + `swap`，每次切页重新下载并可能跳字。改为站内字集 `SmileySans-Subset.woff2`（1234 字形 / 134,596B），覆盖 247 个实际渲染的标题字与 461 个数据库文字；`font-display: optional` + 两个 layout 的 `<link rel=preload>`；前台/后台各一份。实测字体 8–12ms 就绪，FCP 304–428ms，`document.fonts.check` 为 true，无跳变。新增 `tools/build_font_subset.php` 可复现。
- **届次页**：去掉面包屑旁重复的「← 首页」，把「第 N 届」并入副行；上一届/下一届从两张 500px 空卡改成一条文档式翻页条（← 上一届 2018 俄罗斯世界杯 / 下一届 → 2026 美加墨世界杯）。
- **留言簿页**：`/site/discussion` 的 840px 阅读栏从居中改为与面包屑同左轴；标题升为页面 h1，说明移到标题下；空态改为「留言簿还是空的」。`CommentSection` 增加 `headingTag / intro / emptyTitle` 三个可选参数，默认行为不变。
- **关于页**：文档空态去掉虚线框（总纲 §17.5），改为左线说明 + 访客措辞；无文档时主按钮改为弱化的「← 回到首页」文字链。
- **球队 / 球员详情**：历届战绩、名单、生涯表收窄到 880 / 960px（压过 Bootstrap 的 `.table{max-width:100%}`）；三张球员数字卡合并为一条 760px 读数带；移动端保留 2 列名单。
- **平板断点**：淘汰赛在 768–1099 也是 2 列堆叠，但轮次标签原先只在 ≤767 显示；已把标签移到 1099 断点，768 截图确认「决赛/半决赛/1/4/1/8」齐全。
- **后台**：比赛表单成对字段（阶段/状态、主客队、主客比分、点球比分）两列；CRUD 移动端行高压缩、隐藏比赛编号列，按钮高度 28px。
- **字号**：前台/后台所有 11px 元信息抬到 12px；留言簿时间轨、同感、回复时间同步。
- **动效**：去掉逐块记分牌与每个详情页头部的 fade-and-rise，以及跨页 view-transition；只保留首页标题 + 记分牌墙的一次性 0.55s 上浮。切页内容立即可读，不再参与“等半秒”的感觉。
- **验证**：`php -l` 全部通过；前台 11 个路由 200；390 / 768 / 1440 全部 `scrollWidth === innerWidth`（只有 `aria-hidden` 氛围层超出视口且被容器裁切，不产生滚动）；截图在 `runtime/qa/pd1/`。
- **文件**：`frontend/web/fonts/*`、`backend/web/fonts/*`、`tools/build_font_subset.php`、两个 `layouts/main.php`、`frontend/web/css/{design-v2,root-wall,tournament-detail,bracket,catalog,team-info}.css`、`backend/web/css/design-v2.css`、`frontend/views/tournament/*`、`frontend/views/team-info/index.php`、`backend/views/game/_form.php`、`frontend/views/site/discussion.php`、`frontend/views/comment/_section.php`、`frontend/widgets/CommentSection.php`。
- **协作说明**：提交里同时带上了 2026-11-24 留言簿收敛（两本簿 + 全站页）与 win-WorldCup-2 的球员图 include、win-WorldCup-5 的 `art-r5.css` 注册，因为共享工作树里它们与我的改动在同一批文件上；`art-r5.css` 本身已在 `ef052a5` 之前单独提交。
- **仍未做**：比赛详情事实行标签可再退一级；后台 CRUD 操作列的图标/文字混用待统一；移动端进球分布图例翻页藏队（已转给 win-WorldCup-2）。
