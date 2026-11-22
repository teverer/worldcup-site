# 世界杯数据门户网站

**团队：国足世界杯夺冠队**

世界杯数据门户网站是一个基于 Yii2 advanced 模板搭建的团队项目，包含前台数据展示、后台管理以及命令行工具。

## 技术栈

- PHP 8.2
- MySQL 8.0
- Yii2 advanced 2.0.32

## 目录结构

- `frontend/`：前台应用（赛事、球队、球员、留言等页面）
- `backend/`：后台管理应用
- `common/`：前后台共用模型、组件与配置
- `console/`：命令行应用（迁移、定时任务等）
- `docs/`：团队文档（需求、数据库设计、分工、编码规范）
- `data/`：数据库脚本，以及团队/个人数据目录
- `vendor/`：Composer 依赖（已提交进仓库）

## 文档索引

模块②“球队与球员”和模块④“留言与资讯”已实现：前台可浏览球队、球员和资讯，登录用户可以在实体详情页留言、回复和点赞；管理员可以维护资讯和留言。详细入口和测试步骤见对应模块文档。

- [功能清单与数据库表设计](docs/功能清单与数据库表设计.md)
- [数据库表详细设计](docs/数据库表详细设计.md)
- [团队分工方案](docs/团队分工方案.md)
- [编码规范与注释头模板](docs/编码规范与注释头模板.md)
- [Git 使用说明](docs/Git使用说明.md)
- [模块④：留言与资讯](docs/模块4_留言与资讯.md)
- [界面设计规范](docs/界面设计规范.md)
- [数据来源说明](docs/数据来源说明.md)
- [用户手册](docs/用户手册.md)
- [实现文档初稿](docs/实现文档.md)
- [完整文档目录(含阅读顺序)](docs/README.md)

## 本地运行

二选一：

### 方式一：XAMPP Apache

将 `frontend/web` 和 `backend/web` 配置为 Apache 站点根目录（或虚拟主机），启动 XAMPP 的 Apache 与 MySQL 后，通过浏览器访问对应地址。

### 方式二：PHP 内置服务器

在项目根目录分别启动前台与后台：

```bash
php yii serve --port=8080 --docroot=frontend/web
php yii serve --port=8081 --docroot=backend/web
```

- 前台：<http://localhost:8080>
- 后台：<http://localhost:8081>

### 队友首次配置

clone 项目后，先初始化环境配置：

```bash
php init --env=Development --overwrite=All
```

然后修改 `common/config/main-local.php` 中的数据库连接，把用户名、密码改成自己本机的 MySQL 账号密码：

```php
'dsn' => 'mysql:host=localhost;dbname=worldcup',
'username' => '你的用户名',
'password' => '你的密码',
```

## 数据库初始化

先创建 `worldcup` 数据库，再导入建库建表脚本：

```bash
mysql -u root -p < data/install.sql
```

导入后执行自检脚本：

```bash
mysql -u root -p < data/check.sql
```

数据库连接配置位于 `common/config/main-local.php`。

### 模块④演示数据

先导入球队与球员演示数据，再导入留言和资讯数据：

```bash
mysql -u root -p worldcup < data/demo_teams_players.sql
mysql -u root -p worldcup < data/demo_news_comments.sql
```

`demo_news_comments.sql` 可重复执行。

### 模块④测试

模型回归必须使用独立测试库：

```powershell
$env:WC_TEST_DSN = 'mysql:host=127.0.0.1;dbname=worldcup_module4_test'
$env:WC_TEST_USER = 'root'
$env:WC_TEST_PASSWORD = '自己的测试库密码'
php tests/module4/run.php
```

HTTP 冒烟测试的详细环境变量和启动条件见
[模块④设计与使用说明](docs/模块4_留言与资讯.md)。

## 队内约定

- 数据库中的比赛表统一命名为 `game`，不要使用 `match`。
- 留言功能需要登录后才能使用。
- 代码注释头规范见 [编码规范与注释头模板](docs/编码规范与注释头模板.md)。

## 说明

`vendor/` 已提交进仓库，clone 后无需执行 `composer install` 即可运行。
