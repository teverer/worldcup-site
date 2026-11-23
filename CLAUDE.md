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
8. UI 完成定义:`php -l` 通过 + `curl` 200 + 在 390 / 768 / 1440 三档宽度实际查看 + 对照 `docs/界面设计规范.md` §0–1 与总纲 §11

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

## GitHub 连接与推送

- remote:`origin` → `https://github.com/teverer/worldcup-site.git`
- 认证:Git Credential Manager(`git config --get credential.helper` 返回 `manager`)。首次可能弹浏览器授权一次,之后由系统凭据管理器保存;**仓库里没有任何 token 或密码**。
- 代理:git 全局配置 `http.proxy` / `https.proxy` 为 `http://127.0.0.1:7897`(Clash)。推送前确认代理已开启,否则会报 `Failed to connect to 127.0.0.1 port 7897`。
- 标准推送流程:
  ```bash
  git status                 # 先看改了哪些文件
  git add <文件路径>          # 按路径添加,不用 -A
  git commit -m "type(scope): 说明"
  git push origin <当前分支>
  git status -sb             # 确认与远端同步
  ```
- 代理抖动或 TLS 报错时,依次尝试:
  1. 等几秒重试一次;
  2. 绕过代理直连:`git -c http.proxy= -c https.proxy= push origin <分支>`;
  3. 在 Clash 里换节点后再试。
- 只读应急(代理不可用、只想看远端):
  `git -c http.proxy= -c https.proxy= ls-remote --heads https://ghproxy.net/https://github.com/teverer/worldcup-site.git`
  镜像**只用于读**,不要用它 push——凭据会发给第三方。
- 分支:`feature/xxx`、`fix/xxx`;先合 `dev`,`main` 只由队长合;禁止 `push --force`。

## 绝对不能做

- 提交 `*-local.php`、`runtime/`、`web/assets/`
- 直接改 `main`、`git push --force`
- 未确认就修改队友负责的模块文件(归属见下)
- 在视图/控制器里拼接 SQL 字符串;必须用 AR / QueryBuilder 参数绑定
- **编造比赛数据**;真实数据来源见 `docs/数据来源说明.md`
- 把未经检查的子代理产出直接提交
- 用 `git reset --hard`、`git checkout -- .` 丢弃改动;提交前用 `git status` 核对,按路径 `git add`
- 手工对演示库执行临时 `UPDATE/INSERT/DELETE` 做数据修正;数据变更写进 `data/*.sql` 并保持幂等(测试可用临时库)

## 核心约定

设计参考:优先读仓库内 `docs/设计改造-01-感觉与工作流.md`;代码实例:<https://github.com/GoogleChrome/modern-web-guidance-src>
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
- 写 / 改文档前先读 `docs/README.md`「文档写作约定」;规格只写意图、边界、验收,不写像素 / 列数 / 实现处方。

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

## 当前状态(2026-11-23)

- 当前分支:`feature/design-tournament-detail`(第 3 步视觉重做);状态以 `docs/设计改造-00-总纲.md` §12 为准
- 已完成:模块②③④、真实数据(11 届 / 708 场 / 2045 球 / 83 队 / 4282 球员);根页夜场记分牌墙
- 进行中:第 3 步视觉重做完成、待验收;下一步按 `docs/设计改造-00-总纲.md` §16 做索引页母版(比赛先落地,球队 / 球员跟进),之后做单场页
- 未完成:模块①(团队展示、后台编辑、下载页)、部署文档 + `deploy.bat`、6 份 PDF、最终实现文档汇总
- 已知缺口:2026 届名单只含 190 名进球者;教练 / FIFA 排名 / 部分助攻为 NULL

## 协作与子代理规则(用户偏好)

- 优先用子代理并行,但**不要把全部任务塞给同一个**;按任务边界拆分
- 子代理产出必须先验收,再提交
- 视觉判断可以交给用户;先自检到"可展示"再让用户看,不要拿半成品

- 若多会话协作:动手前先 `pull`;一个会话只负责一条分支 / 一组文件;冲突先沟通

## 详细文档索引

- [`docs/项目上下文.md`](docs/项目上下文.md) —— 背景、关键决策、数据管线、遗留问题
- [`docs/设计改造-00-总纲.md`](docs/设计改造-00-总纲.md) —— 本次视觉改造的主要迭代日志,内容意义不大,仅做记录
- [`docs/设计改造-01-感觉与工作流.md`](docs/设计改造-01-感觉与工作流.md) —— 记录的感觉词典、工作流、评审清单,参考性较高
- [`docs/README.md`](docs/README.md) —— 全部文档与推荐阅读顺序
- [`docs/数据来源说明.md`](docs/数据来源说明.md)
- [`docs/界面设计规范.md`](docs/界面设计规范.md)
- [`docs/页面设计指南.md`](docs/页面设计指南.md) —— 重要参考内容,写代码时必看第一文件
- [`docs/编码规范与注释头模板.md`](docs/编码规范与注释头模板.md)
- [`docs/Git使用说明.md`](docs/Git使用说明.md)
- [`docs/数据库表详细设计.md`](docs/数据库表详细设计.md)
