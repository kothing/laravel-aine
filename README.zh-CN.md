# Aine

**Aine** 是一个基于 **Laravel 13** 和 **Vue 3** 构建的、可自托管的 **Headless 内容管理框架（CMF）**。它提供简洁现代的管理后台，用于建模内容、管理多语言数据，并通过强大的 REST 内容 API 将内容发布到任意终端——网站、移动应用、IoT 屏幕或其他后端系统。

> English documentation: [README.md](README.md)

---

## ✨ 功能特性

### 内容建模
- **多项目（Projects）**：单套系统可运行多个独立站点/应用，各自拥有独立的集合（Collections）、内容、媒体、Token 与域名白名单。
- **集合与字段（Collections & Fields）**：可视化构建数据结构，支持 `文本`、`长文本`、`富文本`、`别名(slug)`、`邮箱`、`密码`、`数字`、`枚举`、`布尔`、`颜色`、`日期`、`时间`、`媒体`、`关联(relation)`、`JSON` 等字段类型。
- **字段选项**：必填/唯一/字符数校验、可重复字段、隐藏于 API、列表隐藏、占位符与说明。
- **关联关系**：集合间一对一 / 一对多（如分类 → 文章、作者 → 文章）。
- **预置模板**：可基于 **CMS 模板**（文章、页面、分类、作者、标签、评论、全局）或 **企业黄页模板**（商家、分类、标签、位置、点评）一键创建项目，并可自由扩展。

### 发布与 API
- **两种 API 模式**：
  - `/api/project/{uuid|slug}/...` —— 面向前端应用，通过**域名白名单**校验，可开启 **Public API** 实现免 Token 读取；
  - `/api/{uuid}/...` —— 面向服务端调用，所有请求都需要绑定项目的 **Sanctum Token**。
- **丰富的查询能力**：`where`（等于/不等于/包含/小于/小于等于/大于/大于等于/区间/在列表中/为空/不为空，支持 AND 与 OR）、`whereRelation` 关联过滤、多字段 `sort` 排序、`offset`/`limit` 分页、`count` 计数、`first` 取单条、`state` 发布状态（已发布/草稿）、`timestamps` 时间戳，以及**语言过滤**（`where[locale]=zh`）。
- **媒体库**：按项目上传、列表、获取、删除媒体（支持本地或云存储磁盘）。
- **Webhooks**：按项目配置 Webhook 端点，可指定集合并查看请求日志。

### 安全
- **速率限制**：按用户 / IP 对 API（60 次/分钟）、写入接口（30 次/分钟）、搜索（登录 60 / 匿名 20 次/分钟）、公开表单提交/上传，以及后台认证（密码重置 / 2FA）分别限流。
- **安全响应头**：所有响应默认携带 `X-Content-Type-Options: nosniff`、`Referrer-Policy`、`Permissions-Policy`；后台区域额外启用 `X-Frame-Options: SAMEORIGIN` 防点击劫持。
- **富文本消毒**：富文本保存前经白名单消毒（后台内容与公开表单），移除脚本、事件属性、`javascript:` 链接与危险 CSS。
- **上传守卫**：媒体上传在 MIME 白名单之外叠加扩展名 + 内容 MIME 双重黑名单校验（拒绝 `php`、`phar`、`phtml`、`asp`、`jsp` 等）。

### 多语言
- **内容级语言**：每条内容都带语言标识，可按语言查询与创建内容（`en`、`zh`…）。
- **后台界面语言**：顶栏一键切换后台界面为英文或中文。
- **翻译管理**：全局界面文案翻译 + 项目级翻译（集合名、字段标签、自定义文案），由显式 `__()` helper 与 `{{ ... }}` 模式匹配字典引擎驱动。

### 后台体验
- Vue 3 单页后台（`/admin`）：项目、集合、内容表格、富文本编辑器（TinyMCE）、字段拖拽排序、表单、媒体、设置。
- **网页安装向导**：全新部署访问 `/install` 即可启动向导——语言选择、环境检查、目录权限、环境配置（应用 + 数据库 + 管理员账号）、确认页、自动迁移、安装完成。
- 支持 **SQLite、MySQL、PostgreSQL、SQL Server**。

### 前端
- 前台 SPA（Vue 3）挂载在 `/`，为内容站点提供现成页面：CMS 模板（首页、内容归档、分类、标签、文章详情）与黄页模板（商家列表、分类、标签、位置、点评）——全部数据通过内容 API 加载。

---

## 🧰 技术栈

| 层级 | 技术 |
| --- | --- |
| 后端 | Laravel 13（PHP 8.3+）、Laravel Sanctum、Spatie Permission、Spatie Webhook Server、Intervention Image |
| 前端 | Vue 3、Vite 6、Tailwind CSS 3、Pinia、Vue Router、TinyMCE |
| 数据库 | SQLite / MySQL / PostgreSQL / SQL Server |
| 认证 | 后台会话认证；内容 API 使用 Sanctum 个人访问令牌 |

---

## 📋 环境要求

- **PHP >= 8.3**，需启用扩展：`openssl`、`pdo`、`mbstring`、`tokenizer`、`ctype`、`xml`、`fileinfo`、`gd`、`curl`
- 数据库：SQLite（默认）/ MySQL / PostgreSQL / SQL Server
- Composer 2、Node.js 18+（仅构建前端资源时需要）
- 可写目录：`storage/`、`bootstrap/cache/`、`database/`（SQLite）

---

## 🚀 安装部署

### 方式 A：网页安装向导（推荐）

将项目上传到服务器后，浏览器访问：

```
https://your-domain.com/install
```

向导将引导你完成：

1. **语言选择**（English / 中文）
2. **服务器环境检查**（PHP 版本与扩展）
3. **目录权限检查**
4. **环境配置**——应用名称/地址、数据库（SQLite/MySQL/PgSQL/SQL Server）、**管理员账号**（邮箱 + 密码，自动成为 `super_admin`）
5. **确认页**——核对配置，可返回修改，或开始安装
6. **执行安装**——写入 `.env`、生成 `APP_KEY`、自动迁移数据库、创建管理员
7. **完成**——用刚才创建的账号登录 `/admin`

> 安装向导支持全新部署（无 `.env` 文件）：会自动创建 `.env` 并生成密钥。

### 方式 B：手动安装

```bash
# 1. 安装依赖
composer install
npm install

# 2. 环境配置
cp .env.example .env
php artisan key:generate
# 编辑 .env：APP_URL、DB_CONNECTION、DB_DATABASE 等

# 3. 迁移与种子数据（创建 super_admin 角色 + 默认管理员）
php artisan migrate --force
php artisan db:seed --force

# 4. 构建前端资源
npm run build

# 5. 启动
php artisan serve
```

默认管理员（种子数据）：`admin@admin.com` / `admin` —— **首次登录后请立即修改**。

### 方式 C：Docker 部署（生产）

项目内置生产镜像与编排文件：

```bash
# 1. 配置环境变量
cp .env.example .env
# 修改 .env：APP_URL、DB_DATABASE、DB_USERNAME、DB_PASSWORD、APP_PORT

# 2. 构建并启动
docker compose -f docker-compose.production.yml up -d --build

# 3. 首次安装：浏览器打开 http://localhost/install（或你的 APP_URL）
#    按 Web 安装向导完成安装（会生成 .env 与 storage/installed 标记）
```

特点：

- **多阶段构建 `Dockerfile`**：`node:22` 构建 Vite 资源、`composer` 安装生产依赖、`php:8.3-fpm-alpine` 运行应用（无开发依赖、运行时不挂载源码）
- **nginx**：托管静态资源（带 hash 的 `public/build` 资源长缓存），并将 PHP 请求转发至 `app:9000`；内置 SPA history 路由
- **MySQL 8.4**：带健康检查（`app` 等待 `mysql` 就绪后才启动）与持久化数据卷
- **数据持久化**：上传文件 / 日志 / 缓存位于 `aine-storage` 卷；容器启动时自动创建 `storage:link`，媒体文件可直接访问
- **首次运行**：Web 安装向导生成 `.env` 与 `storage/installed` 标记；标记存在后，容器重启时自动缓存 config 与路由

发布新代码：`docker compose -f docker-compose.production.yml build app` 后 `up -d`。HTTPS 请在暴露端口前的反向代理（Caddy / nginx / 云负载均衡）上终结 TLS。

---

## 🏁 快速开始

1. 登录 `/admin`。
2. **创建项目**（或使用预置模板：CMS / 企业黄页）。
3. 在项目中创建**集合**（如 `articles`）并添加**字段**（如 `title`、`url`、`content`）。
4. 在 内容 → 集合 下添加**内容**。
5. 打开 **设置 → API**：
   - 将前端域名加入**域名白名单**；
   - 创建**访问令牌**（勾选 `read` / `write` 权限）；
   - 可选：开启 **Public API** 实现免 Token 读取。
6. 调用 API：

```bash
# 公开读取（域名白名单 + Public API 开启）
curl -H "Origin: https://your-frontend.com" \
     "https://your-domain.com/api/project/my-blog/articles?limit=10&sort=published_at:desc"

# 受保护读取（需 Token）
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Origin: https://your-frontend.com" \
     "https://your-domain.com/api/project/my-blog/articles"

# 服务端调用（UUID + Token，无需白名单）
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://your-domain.com/api/6ae5aa2e-6b1b-4711-a258-a0d8d47611c4/articles?where%5Blocale%5D=zh"
```

---

## 📖 API 文档

完整的 API 参考（认证、接口列表、查询参数、Where 条件、响应格式、示例、常见问题）请参阅 **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)**。

快速总览：

| 模式 | 路由前缀 | 读取认证 | 写入认证 |
| --- | --- | --- | --- |
| 方式 1 — 白名单 | `/api/project/{identifier}/...` | 域名白名单（未开 Public API 时需 Token） | 白名单 + Token（`write`） |
| 方式 2 — UUID | `/api/{uuid}/...` | UUID + Token（`read`） | UUID + Token（`write`） |

---

## 🌐 多语言

- **后台界面语言**：顶栏语言选择器切换（English / 中文），按浏览器记忆选择。
- **语言管理**：侧边栏 `Localization` 页面——添加语言、设置默认显示语言。字典 key 始终为英文源字符串（基准语言）。
- **界面翻译**：`Translations` 页面——翻译后台任意界面文案。
- **项目级翻译**：项目 → 设置 → Translations——翻译该项目后台使用的集合名、字段标签与自定义文案。
- **内容语言**：创建内容时指定 `locale`（`en`、`zh`…）；API 查询用 `where[locale]=...` 过滤。

---

## 🧑‍💻 二次开发

### 目录结构

```
app/
  Aine/                     # 核心辅助类与项目模板
  Http/Controllers/Admin/   # 后台控制器
  Http/Controllers/API/     # 内容 API 控制器
  Http/Resources/           # API 资源（ContentResource、ProjectResource、MediaResource）
  Models/                   # Content、ContentMeta、Project、Collection、CollectionField、Media 等
bootstrap/app.php           # 应用引导、中间件、异常处理
config/installer.php        # 网页安装向导配置
database/                   # 迁移与种子数据
resources/js/admin/         # 后台 SPA（Vue 3）
resources/js/frontend/      # 前台 SPA（Vue 3）
resources/views/            # Blade 视图（安装向导、认证、SPA 外壳）
routes/                     # web、api、admin、frontend、auth 路由
installer/                  # 项目内置网页安装向导包（Aine\Installer）
```

### 常用命令

```bash
# 开发时监听并重建前端/后台资源
npm run dev

# 生产构建
npm run build

# 运行测试
php artisan test

# 清理各类缓存（修改配置/路由后）
php artisan optimize:clear
```

### 新增前台页面

1. 在 `resources/js/frontend/views/` 创建视图组件。
2. 在 `resources/js/frontend/routes.js` 注册路由。
3. 通过内容 API（`resources/js/frontend/api.js`）获取数据，项目标识符在 `resources/js/frontend/config.js`（`PROJECTS` 映射）中配置。

### 扩展 API

- API 接口位于 `app/Http/Controllers/API/`；响应辅助方法在 `API/Concerns/ApiResponse.php`。
- 序列化由 `app/Http/Resources/ContentResource.php` 控制（类型转换、`hiddenInAPI`、可重复字段）。
- 新增字段类型：扩展后台字段注册表（`resources/js/admin/views/Project.Collection/CollectionList.vue` 中的 `fieldDetails`）以及对应的校验/序列化逻辑。

### 项目模板

项目模板（CMS、企业黄页）定义在 `app/Aine/ProjectTemplates.php`——演示项目（`database/seeders/DemoProjectsSeeder.php`）的集合、字段与演示数据均来自模板。

### 后台界面翻译（开发规范）

后台界面以英文为基准语言编写。翻译存储在**数据库**中，并通过**字典接口**下发到浏览器（`GET /admin-api/translations/dict?locale=…`）。所有用户可见文案都必须通过显式、响应式的 `__()` helper 输出——系统**已不再有 DOM 扫描兜底**：未包裹的文案只会一直保持英文原文。

**架构说明**

| 组成部分 | 作用 |
| --- | --- |
| `admin_string_sources` | 可翻译字符串注册表，数据来自 `database/seeders/data/admin_strings.php` |
| `admin_translation_defaults` | 随项目分发的各语言出厂默认翻译（如 `zh`） |
| `translations` | 运行时翻译——可在后台编辑（多语言 → Translations） |
| `ui_locales` | 后台界面语言列表 |
| `resources/js/admin/translations/engine.js` | 字典层：供模板与脚本使用的响应式 `__()` helper、localStorage 缓存（启动时同步应用已保存语言、无闪屏）、以及 `{{ ... }}` 占位符模式匹配（处理含运行时值的文案） |
| `scripts/extract-admin-strings.js` | 扫描 `resources/js/admin`（`.vue`/`.js`），把 JS/Vue 插值归一化为 `{{ ... }}` 占位符，并重新生成注册表种子文件 |

**开发者必须遵守的规范**

1. 所有用户可见文案都要用显式 helper 包裹：
   - 模板：`{{ __('Save and close') }}`
   - 脚本与绑定属性：`toast.success(__('Content updated!'))`、`:placeholder="__('Search...')"`
2. 含运行时值的文案，源字符串里保留 `{{ ... }}` 占位符，不要拼接翻译后的碎片，例如 `__('Language "{{ ... }}" added.', [code])`——位置参数按顺序填充占位符。
3. **所有文案都必须包裹 `__()`**——已不存在 DOM 扫描兜底，未包裹的文案会一直停留在基准语言。

**翻译者规则（多语言 → Translations）**

- 字典的 key 永远是英文源字符串；只翻译 `{{ ... }}` 占位符周围的文字。
- `{{ ... }}` 占位符必须**原样保留**，且**数量与顺序必须与源字符串一致**——引擎按顺序把运行时值填入这些占位符。删减、新增或调换占位符都会导致运行时文案出错。

**新增一个可翻译文案的流程**

```bash
# 1. 从源码重新生成注册表
node scripts/extract-admin-strings.js

# 2. 把注册表与默认翻译同步进数据库
php artisan db:seed --class=AdminTranslationsSeeder

# 3. 到后台填写翻译：多语言 → Translations
```

注意事项：
- seeder 只写入**新建或空值**的 `translations` 行——后台已编辑过的翻译永远不会被覆盖。
- 注册表只增不减：从代码中删除的字符串仍会保留在注册表中，其翻译不会丢失。
- 写入翻译（`POST /admin-api/translations/save`、`/add`）与界面语言管理（`/admin-api/localization/*`）仅限 `super_admin` 角色。

---

## 🧪 测试

```bash
php artisan test
```

现有功能测试验证前端首页响应正常；可随功能开发在 `tests/` 中扩展。

---

## 📄 开源协议

**Aine** 以 [MIT 协议](LICENSE) 开源。
