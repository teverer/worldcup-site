# CLAUDE.md — 世界杯数据门户

> 每次会话的开工须知。详细背景、决策记录和遗留问题见 [`docs/项目上下文.md`](docs/项目上下文.md)。
> 本项目可能有多个 Claude 会话并行协作,动手前先看「协作规则」。

## 项目

- 团队:**国足世界杯夺冠队**(4 人课程设计)
- 技术栈:PHP 8.2 + MySQL 8.0 + Yii2 advanced 2.0.32(MVC)
- 仓库:<https://github.com/teverer/worldcup-site>(公开)
- 截止:**2026-11-30**
- 定位:**世界杯历史档案 + 社区讨论**(不是比分直播站)

## 每次开工

1. `git status` 确认干净 → `git pull --rebase origin dev`
2. 开分支:`feature/xxx` 或 `fix/xxx`,不要直接改 `main`
3. 改完自检:`php -l` + `curl` 页面 200 + Chrome 无头截图
4. 提交格式:`type(scope): 说明`(如 `feat(game): 比赛列表支持按届次筛选`)
5. 合入 `dev`,队长定期合 `main`
6. 多会话并行时,先声明你要改哪些文件,避免两个会话改同一处
7. 改了 CSS 但页面没变化:删除 `frontend/web/assets/` 下的哈希目录,刷新重新发布
8. UI 完成定义:`php -l` 通过 + `curl` 200 + 在 390 / 768 / 1440 三档宽度实际查看 + 对照 `docs/界面设计规范.md` 第 1–6 节

## 本地环境

- 启动 MySQL80(需管理员,会弹 UAC):
  ```bash
  powershell -NoProfile -Command "Start-Process -FilePath 'net' -ArgumentList 'start','MySQL80' -Verb RunAs"
  ```
- 前台:`php yii serve --port=8080 --docroot=frontend/web`
- 后台:`php yii serve --port=8081 --docroot=backend/web`
- 账号:`admin / admin123`(后台)、`demo / admin123`(前台)
- 数据库:`worldcup`;连接配置在 `common/config/main-local.php`(**含密码,严禁提交**)
- 截图与备份:`runtime/qa/`、`runtime/backup/`(已 gitignore)
- 网络:git 代理 `127.0.0.1:7897`;只读应急镜像 `https://ghproxy.net/https://github.com/...`
- Chrome:`C:\Program Files\Google\Chrome\Application\chrome.exe`(无头截图)
- 设计参考:`E:\aaawendang\references\modern-web-guidance`

## 绝对不能做

- 提交 `*-local.php`、`runtime/`、`web/assets/`
- 直接改 `main`、`git push --force`
- 未确认就修改队友负责的模块文件(归属见下)
- 在视图/控制器里拼接 SQL 字符串;必须用 AR / QueryBuilder 参数绑定
- **编造比赛数据**;真实数据来源见 `docs/数据来源说明.md`
- 把未经检查的子代理产出直接提交
- 运行 `composer install/update/require`(`vendor/` 已提交);需要新库先问队长
- 用 `git reset --hard`、`git checkout -- .` 丢弃改动;提交前用 `git status` 核对,按路径 `git add`
- 手工对演示库执行临时 `UPDATE/INSERT/DELETE` 做数据修正;数据变更写进 `data/*.sql` 并保持幂等(测试可用临时库)

## 核心约定

- 比赛表叫 **`game`**,不叫 `match`(`match` 是 PHP 8 保留字,模型类无法编译)
- 留言/点赞需要登录;`comment.user_id` 非空
- 统计口径:
  - 积分榜只算 `stage='小组赛' AND status=2`;胜场积分取 `tournament.win_points`(1986/1990 为 2 分)
  - 射手榜排除乌龙球(`is_own_goal=1`)与点球大战进球(`is_shootout=1`)
  - 各队进球按 `goal.team_id` 统计,乌龙球归受益方
- 每个 PHP 文件必须有注释头:
  ```php
  /**
   * Team: 国足世界杯夺冠队
   * Coding by 姓名 学号, YYYYMMDD
   * 模块:xxx —— 文件用途
   */
  ```
- 命名与格式见 `docs/编码规范与注释头模板.md`;UTF-8 无 BOM、LF、4 空格、PHP 文件结尾不加 `?>`
- 图表配色优先取 `theme.css` 令牌(绿 `#0E7C4A` / `#16A34A`、金 `#F2B705`、藏青 `#0B1F3A`、灰 `#6B7280` / `#9CA3AF`);需要新色时先加到 `theme.css`,不用 ECharts 默认色板
- 新页面或改版前先读 `docs/页面设计指南.md`(页面组织),视觉细节看 `docs/界面设计规范.md`
- 视觉改造以 `docs/设计改造-00-总纲.md` 为准;根页 = 记分牌墙,全站统一母版
- 规则是下限,不是上限:与“感觉”冲突时先提案突破并记录理由,不要为了守规则牺牲视觉

## 课程硬性要求

- 数据库 >10 张表(现有 14 张)
- 建库脚本 `data/install.sql`
- 团队作业放 `data/team/`,个人作业放 `data/personal/`,网站提供下载链接
- 每个组员至少编写 MVC 三层各一个文件
- 6 份文档:需求 / 设计 / 实现 / 用户手册 / 部署 / 项目展示 PPT
  - 命名:`队名_文档名(学号1_学号2_学号3).pdf`
- 动态图形为加分项(已实现 3 个图表)

## 模块归属

| 模块 | 负责人 | 内容 |
|---|---|---|
| ① 团队主页 | GitHub `vvvvvvprog` | 团队展示前后台、作业下载页 |
| ② 球队与球员 | GitHub `kighter123456` | `team` / `player` 前后台 |
| ③ 届次与比赛 | 李行阳(队长) | `tournament` / `squad` / `game` / `goal`、晋级图、图表 |
| ④ 留言与资讯 | GitHub `Andrewgarfiled` | `comment` / `comment_like` / `news` 前后台 |

> 全站页面设计(theme.css、布局、首页、届次页视觉)由队长负责,**不属于模块①**。

## 代码地图

| 内容 | 路径 |
|---|---|
| 数据模型 | `common/models/` |
| 前台控制器 / 视图 | `frontend/controllers/`、`frontend/views/` |
| 后台控制器 / 视图 | `backend/controllers/`、`backend/views/` |
| 设计系统与配色 | `frontend/web/css/theme.css`、`docs/界面设计规范.md` |
| 前后台布局 | `frontend/views/layouts/main.php`、`backend/views/layouts/main.php` |
| 首页 | `frontend/views/site/index.php` + `frontend/models/HomeStats.php` |
| 届次列表 | `frontend/views/tournament/index.php` + `frontend/web/css/tournament-index.css` |
| 届次详情 / 晋级图 | `frontend/views/tournament/view.php`、`_bracket.php`、`_groups.php`、`_stats.php`、`_history.php`;数据在 `frontend/models/TournamentDetail.php` |
| 图表 | `frontend/controllers/ChartController.php`、`frontend/views/chart/_charts.php`、`frontend/web/js/charts.js` |
| 建库与数据 | `data/install.sql`、`data/real_worldcup_data.sql`、`data/real_worldcup_2026.sql` |

## 当前状态(2026-11-22)

- 分支:`dev` 已合入根页记分牌墙与设计总纲,已推送;设计改造进行中
- 已完成:模块②③④、真实数据(11 届 / 708 场 / 2045 球 / 83 队 / 4282 球员);根页夜场记分牌墙
- 进行中:第 3 步 届次详情页(见 `docs/设计改造-00-总纲.md`)
- 未完成:模块①(团队展示、后台编辑、下载页)、部署文档 + `deploy.bat`、6 份 PDF、最终实现文档汇总
- 已知缺口:2026 届名单只含 190 名进球者;教练 / FIFA 排名 / 部分助攻为 NULL

## 协作与子代理规则(用户偏好)

- 优先用子代理并行,但**不要把全部任务塞给同一个**;按文件边界拆分
- 子代理产出必须先验收(`php -l`、curl、截图),再提交
- 视觉判断可以交给用户;先自检到"可展示"再让用户看,不要拿半成品
- 汇报简明,不粘贴大段代码或完整日志
- 多会话协作:动手前先 `pull`;一个会话只负责一条分支 / 一组文件;冲突先沟通

## 详细文档索引

- [`docs/项目上下文.md`](docs/项目上下文.md) —— 背景、关键决策、数据管线、遗留问题
- [`docs/设计改造-00-总纲.md`](docs/设计改造-00-总纲.md) —— 本次视觉改造的总纲、色板、页面规格与迭代日志
- [`docs/README.md`](docs/README.md) —— 全部文档与推荐阅读顺序
- [`docs/数据来源说明.md`](docs/数据来源说明.md)
- [`docs/界面设计规范.md`](docs/界面设计规范.md)
- [`docs/页面设计指南.md`](docs/页面设计指南.md) —— 页面构成方法与开工模板
- [`docs/编码规范与注释头模板.md`](docs/编码规范与注释头模板.md)
- [`docs/Git使用说明.md`](docs/Git使用说明.md)
- [`docs/数据库表详细设计.md`](docs/数据库表详细设计.md)
