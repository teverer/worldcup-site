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

### 16:50 · win-WorldCup-4 · 桌面“大气版”（用户第二批反馈）
- **用户反馈**：移动端暂不重点（老师用笔记本检查，暂不公网部署）；首页“最新留言 / 关于与文档”太淡、和上面融在一起；多处不大气——届次行纵向太窄、晋级图只占 2/3、两侧空。
- **首页**：届次行纵向 padding 28→40、行高约 200px；年份/队名/冠军/比分/场次全部上调。新增独立“收场”深色带（`--night-2` + 金色顶线 + 100vmax 出血），标题「继续看，或者留一句」；内部两列：最新留言（空态有标题、副句、描边 CTA）/ 换一种找法（4 条大号导航 + 关于本站与文档）。不再有孤零零的说明链接。
- **晋级图**：决赛 430→800、半决赛 340→560、1/4 232→300，1/8/半决赛/决赛高度 60/68/80/112 → 72/88/110/160；字号与比分同步放大。1366/1280 实测上层三角形填满舞台，不再只占中间 2/3。
- **其他大气点**：比赛比分板 padding/比分 64→88、队名 22→26；届次 hero facts 数字 2.6→3rem、padding 加大；球队/球员深色头 padding 42、h1 44px；索引页标题 36→44、导语 16.5、行高与卡片内边距加大；新闻列表行 padding 18→28、标题 22；球队详情新增右侧 300px「队史总计 / 参赛届次 / 数据口径」栏（主栏 872 收窄到可读行宽，右侧不再空）；球员读数带全宽、生涯图与生涯表同宽 960。
- **移动端**：降级为“不溢出兜底”，390 未投入新设计；后续若要公网部署再单独做一版。
- **验证**：`php -l`；首页 / 届次 / 比赛 / 球队 / 球员 / 资讯 / 关于 / 留言簿 200；1280 / 1366 / 1440 无页面级横向溢出；截图 `runtime/qa/pd1/g-*.png`。
- **协作**：已在 peer 广播“移动端降级 + 桌面大气”方向；-5 只补收场带颜色/纹理，-2 留意图表容器变宽后的轴标签。

### 17:05 · win-WorldCup-2 · 姓名切换跳 tab 修复 + 零样本处理
- **Bug**：统计 tab 里点「中文名/原名」会回到淘汰赛。原因：`Url::current()` 不含 hash，redirect 回跳丢掉了 `#charts`。修法：`_name_lang.php` 支持调用方传 `returnUrl`；`_stats.php` 传 `Url::current() . '#charts'`。CDP 实测点完后 URL 仍是 `...#charts`，统计面板 visible。
- **零进球球员**：不做假数据。球员图改为以「参赛届次」为轴，补上 0 球 0 助攻的届次（Messi 2010 现已出现）；完全 0 参与的球员不画图，显示「0 球 · 0 助攻 —— 样本不足，不绘制生涯图」并链接到生涯表。
- **顶级站参考**：FootyCabin 球员页在样本不足时直接不画 profile chart，并显示 "Insufficient sample"；CricketStudio 对低于阈值的统计不展示或标 sub-floor；FBPlot 建议按任务设阈值并使用谨慎措辞。我们采用「原始计数仍进表 + 派生图在零样本时不画 + 明确说明」。
- **配色校验**：用 dataviz 的 OKLab 校验脚本重排球队色，最差相邻对正常视觉 ΔE 16.7、CVD 15.0，均过线；品牌低饱和导致亮度/彩度两项不达标，保留品牌色并在图表里用直接标签 + 数据表兜底。
- 验证：`php -l`、`node --check`、CDP 点击测试、390/1440 截图。

### 17:35 · win-WorldCup-2 · 图表按需加载 + 配色校验
- **性能**：ECharts 约 1MB，原来统计页/球员页首屏就下载。现在 `charts.js` 在统计面板第一次可见时才注入 `echarts.min.js`；球员图用 `IntersectionObserver`（rootMargin 240px）接近视口再加载。实测：届次页默认态 0 次 ECharts 请求，点「统计」后 1 次、3 张图；球员页滚到图表附近才 1 次。
- **校验**：用 dataviz 技能的 OKLab 脚本重排球色，最差相邻对正常视觉 ΔE 16.7、CVD 15.0；品牌低饱和导致亮度/彩度两项不达标，保留品牌色，用直接标签 + 数据表兜底。
- **原型**：试过 100% 堆叠条（ribbon）替代饼图，前 7 段标签在 640px 卡内过挤、不如圆环可读，未采用；保留「圆环 + 右侧排名图例」。
- 验证：`php -l`、`node --check`、CDP 网络请求计数、390/1440 截图；球员图在 390 下 6 届仍可读。

### 18:05 · win-WorldCup-2 · 球队「世界杯轨迹」图
- 新图：球队详情「历届战绩」里加一条最终阶段折线（小组赛→32强→…→决赛），冠军用金点并直标「冠军」；按参赛届次排列，不足两届不画。数据来自现有 `Team::tournamentRecords()`；补了 `final_*` 与 `champion` 字段（点球大战按罚球分判定）。
- 已验证：阿根廷（1986/2022/2026 冠军金点、2026 决赛非冠军）、西班牙（2010/2026 冠军）、390 窄屏年份隔一个显示、无页面级溢出。ECharts 仍按需加载。
- 文件：`common/models/Team.php`、`frontend/views/team/_history_chart.php`（新）、`frontend/web/css/team-chart.css`（新）、`frontend/web/js/charts.js`；`frontend/views/team/view.php` 一行 include（该文件有其他会话未提交改动，未提交）。

### 18:40 · win-WorldCup-2 · 比赛「比分走势」图
- 比赛详情新增阶梯线：两队各一条累计比分线，进球点可悬停看球员、点球/乌龙、当时比分；90–120 画「加时」底纹；点球大战不入图，仍由下方时间线单独呈现。
- 不足 2 个常规进球不渲染（0–0、1–0 比赛已验证无图），把「样本不足」留给时间线，不画空图。
- 复用图表懒加载；390/1440 截图通过，无横向溢出。
- 文件：`frontend/views/game/_score_progress.php`（新）、`frontend/web/css/game-chart.css`（新）、`frontend/web/js/charts.js`；`frontend/views/game/view.php` 一行 include（该文件有其他会话未提交改动）。
