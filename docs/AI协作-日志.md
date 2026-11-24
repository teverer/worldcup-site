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
