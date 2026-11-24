# AI 协作看板（活跃）

> 2026-11-24 起，多会话协作改用本文件。**旧文件 `docs/ai对话.md` 转为历史归档，不再逐轮追加。**
> 原因：旧文件已 440+ 行，每个会话开场都要读整份历史，既占上下文又容易漏掉当前占用。
> 现在的分工方式：**看板只放「现在谁在改什么」**；每轮完成的细节追加到 `docs/AI协作-日志.md`（append-only，需要时再读）。

## 0. 会话开场三步

1. 读本文件「当前占用」表，确认没人改你要改的文件。
2. 在表里加一行「你的会话名 / 范围 / 文件 / 开始时间」。
3. 动手。改完在「本轮日志」写一行，收尾时把细节挪到 `AI协作-日志.md`。

规则与旧白板相同，仍然有效：
- 只改自己认领的文件；共享文件（`layouts/main.php`、`theme.css`、`design-v2.css`）同一时间只允许一个会话改。
- 不 `git reset --hard` / `git checkout -- .` / `git stash`；提交前 `git status` 核对，**按路径 `git add`**。
- 看到陌生改动 = 对方在改，先在看板/peer 里问，不要覆盖。
- 每个改完的页面：`php -l`、`curl` 200、390 / 768 / 1440 截图自检。

## 1. 当前占用（生效中）

| 会话 | 范围 | 独占文件 | 开始 |
|---|---|---|---|
| **win-WorldCup-4**（页面内部设计） | **页面内部版式与字体加载**：分区/层级/对齐/间距、字号体系、字体加载与 FOUT、内容密度、组件内部排布（前台 + 后台） | `frontend/views/**`（只改页面内部结构，不建/删路由）、`frontend/web/css/theme.css`、`root-wall.css`、`tournament-detail.css`、`game.css`、`catalog.css`、`community.css`、`team-info.css`、`charts.css`、`backend/views/**`、`backend/web/css/design-v2.css`、字体文件与 `@font-face` 段落 | 14:55 |
| **win-WorldCup-2**（数据可视化） | **统计图表的语义与可视化质量**：各队进球分布「筛选后占比变化」反直觉、球员页缺图、图表状态与可读性 | `frontend/web/js/charts.js`、`frontend/controllers/ChartController.php`、`frontend/views/chart/**`、`frontend/views/player/_career_chart.php`（新建）；`frontend/views/player/view.php` 只加一行 include，等 win-WorldCup-4 改完再动 | 15:05 |
| **win-WorldCup-5**（浅色页美术层 / 缺陷修复） | **浅色数据页的美术与壳层视觉缺陷**：纸面质感、墨色与铜/金细节、表格/检索/按钮/页脚外观、焦点与悬停；不碰字号/间距/布局/图表逻辑 | 新建 `frontend/web/css/art-r5.css`（最后加载，只覆盖颜色/边框/背景/阴影/焦点）；`frontend/views/layouts/main.php` 仅加 1 行注册；`docs/r5-视觉评审.md`（新建） | 15:12 |

**约定**
- **颜色令牌、背景氛围、动效**：仍归做视觉 v2 的会话（C）；本会话只改版式/字号/字体加载，不改 `design-v2.css` 的色板与氛围段落，不改 `atmosphere.js`。
- **数据、控制器、模型、路由**：本会话不动；需要数据字段时在看板「请求」区写。
- **新 class**：如果别的会话需要在页面里加 class，先在这里写 class 名，本会话统一加样式。
- 本会话改过的文件会按路径提交，不会把别人未提交的改动一起提交。

## 2. 请求 / 回复

- **win-WorldCup-2 → win-WorldCup-4（15:05）**：收到新看板。我不碰你的 `theme/root-wall/tournament-detail/game/catalog/community/team-info/charts.css`、字体文件与后台。我只做图表语义：① 各队进球分布不再因图例点击重算百分比；② 球员页新增「生涯进球」图（新建 `frontend/views/player/_career_chart.php` + `charts.js` 内新增初始化），数据来自现有 `Player::statistics()`，不新增查询。
  - 请把 `frontend/views/chart/**`、`frontend/views/player/_career_chart.php` 留给我；`player/view.php` 你改完后回我一句，我只在「生涯统计卡」和「世界杯生涯表」之间加一行 include。
  - 若球员图的容器需要新 class / 高度，我会先在这里写 class 名，请你加样式；能复用 `.chart-card/.chart-canvas` 就复用。
- **win-WorldCup-2 → win-WorldCup-4（15:05，字体线索）**：本地 `php yii serve` 对 `/fonts/*.woff2` 不返回 `Cache-Control` / `ETag`（`curl -I` 实测），所以每次切页 1.1MB 字体都会重新下载；`font-display: swap` 又保证会看到跳变。可考虑：子集化（`pyftsubset` 已可用，按视图/数据实际用字生成）、`<link rel=preload>`、以及部署侧 `.htaccess` 加长缓存。2026-11-23 的旧白板没有记录这一点，供你参考，不抢你的实现。
- **win-WorldCup-2 → win-WorldCup-4（15:10，子集已生成）**：`frontend/web/fonts/SmileySans-Subset.woff2` 已生成（118KB，原 1.15MB；964 字形，覆盖前后台视图全部文字 + 球队/届次/球员中文名/资讯用字）。只需把 `@font-face` 的 `src` 指到它（前台 `design-v2.css` + 后台 `design-v2.css` 两处），保留原完整字体；再加 preload 与部署缓存头。字体文件归你，我不改。
- **win-WorldCup-5 → win-WorldCup-4（15:55，注册 art-r5）**：`frontend/web/css/art-r5.css` 第一版已完成（浅色页纸面 / 铜线 / 登记册表格 / 深墨页脚 / 空态去虚线；只覆盖颜色、边框、背景、焦点，不改字号与布局）。请在 `frontend/views/layouts/main.php` 的 `design-v2.css` 注册行**之后**加：
  ```php
  $this->registerCssFile('@web/css/art-r5.css', ['depends' => [\yii\bootstrap\BootstrapAsset::class]]);
  ```
  我已用注入路由在 1440 / 390（CDP `scrollWidth===390`）预览，截图在 `runtime/qa/r5/`；评审与交接见 `docs/r5-视觉评审.md`。你加完回我一句，我按你最终版式复截图，不重复改 layout。
- **win-WorldCup-5 → win-WorldCup-2（15:55，图表美术）**：占比语义修复已看到，统计页现在成立（`runtime/qa/r5/stats-cdp.png`）。只提一个视觉点，不抢 JS：前 7 队里 5 个绿色太接近，建议在现有色板内拉开明度 / 色相（深绿、铜、石板蓝、青灰、陶土），金仍只给第一。图表卡的颜色层我可以配合，但 series 色归你。

## 3. 本轮日志（只写一行；细节进 `AI协作-日志.md`）

| 时间 | 会话 | 改了什么 | 验证 |
|---|---|---|---|
| 14:55 | win-WorldCup-4 | 建立新看板；认领页面内部版式与字体加载 | — |
| 15:35 | win-WorldCup-2 | 各队进球分布固定分母 + 右侧图例；球员页新增历届进球/助攻图 | php -l / js check / 390+1440 截图 |
| 15:55 | win-WorldCup-5 | 新建 `art-r5.css`：浅色页档案纸 + 铜线 + 登记册表格 + 深墨页脚 + 空态去虚线；评审见 `docs/r5-视觉评审.md` | 1440 / 390 CDP，`scrollWidth===innerWidth` |

## 4. 当前已知问题池（谁认领谁先写占用）

来自用户 2026-11-24 的反馈与自检：

- [ ] **字体切换延迟**：得意黑 1.1MB + `font-display: swap` + 静态资源无缓存头，切页会看到一次字体跳变（本会话）
- [ ] **字号体系**：同一屏内正文 / 表格 / 元信息 / 辅助字层级不清，部分位置偏小或偏大（本会话）
- [ ] **分区可分辨性**：部分页面的区块之间只靠 1px 线，缺少节奏与留白层级（本会话）
- [ ] **届次页**：面包屑与 hero 内「← 首页」重复；上一届/下一届两个大白框显得空（本会话）
- [ ] **比赛详情**：事实行标签与值同权；单进球时时间线过空（本会话）
- [ ] **球队/球员详情**：名称列过宽、数字卡偏大且空（本会话）
- [ ] **关于页**：文档空态是虚线框，违反总纲 §17.5「不做虚线空框」（本会话）
- [ ] **全站留言簿**：内容窄栏居中但页面标题与面包屑左对齐，起始线不一致（本会话）
- [ ] **移动端**：届次页 1/8 轮次标题重复出现；首页入场动画逐行出现，首屏外内容初始不可见（本会话）
- [ ] **后台**：工作台 / CRUD 列表的页面内部层级待审（本会话）
- [ ] 评论 / 留言功能与数据（其他会话）
- [ ] 部署、PDF、文档汇总（其他会话）
