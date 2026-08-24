# API 接口文档

> 本文档基于当前项目 API 实际实现编写，接口行为以代码为准。

## 📋 目录

1. [概述](#概述)
2. [认证方式](#认证方式)
3. [方式 1：域名白名单接口](#方式-1域名白名单接口)
4. [方式 2：UUID + Token 接口](#方式-2uuid-token接口)
5. [接口列表](#接口列表)
6. [参数说明](#参数说明)
7. [Where 条件](#where-条件)
8. [关联条件](#关联条件)
9. [响应格式](#响应格式)
10. [错误响应](#错误响应)
11. [使用示例](#使用示例)
12. [快速开始](#快速开始)
13. [常见问题](#常见问题)

***

## 概述

本 CMS 提供 RESTful API 接口，支持两种调用方式：

### ✅ 方式 1：域名白名单接口（显式项目标识符）

- **路由前缀**：`/api/project/{project_identifier}/...`
- **适用场景**：Vue、React 等纯前端项目，无法在前端代码中隐藏 Token
- **验证机制**：
  - 读取操作：**域名白名单验证**；若项目未开启 Public API，还需 **Bearer Token**（`read` 权限）
  - 写入操作：域名白名单验证 + Bearer Token（`write` 权限）
  - **同源请求**（Origin 与站点域名一致，如 CMS 自托管前端）自动放行，无需配置白名单
- **项目标识符**：UUID 或 Slug（如 `/api/project/my-blog/...`）

### ✅ 方式 2：UUID + Token 接口

- **路由前缀**：`/api/{uuid}/...`
- **适用场景**：Laravel、Java 等后端项目，可在服务端隐藏 Token
- **验证机制**：**所有操作**（含读取）都必须提供 Bearer Token（无公开模式），Token 必须属于该项目且拥有对应权限

***

## 认证方式

### 1. Bearer Token 认证

**请求头**：

```
Authorization: Bearer {your_access_token}
```

**获取方式**：后台管理 → 项目设置 → API Settings → Access Tokens 创建。

**Token 权限（abilities）**：创建 Token 时勾选权限：

| 权限    | 允许的操作         |
| ----- | ------------- |
| `read`  | GET 读取接口（列表/详情/关联/媒体读取） |
| `write` | POST / DELETE 写入接口（创建/更新/删除/上传） |

> Token 同时绑定创建它的项目，只能访问该项目的数据。

### 2. 域名白名单验证（仅方式 1）

**请求头**：

```
Origin: https://your-frontend.com
```

**配置方式**：后台管理 → 项目设置 → API Settings → Domain Whitelist 添加域名（需包含协议，如 `https://your-frontend.com`）。

**作用**：验证跨域请求的来源域名是否在项目白名单中，防止未授权的跨域访问。未带 Origin/Referer 头或同源请求不受白名单限制。

### 3. Public API（仅影响方式 1 的读取）

**条件**：项目设置中开启 `public_api` 选项。

**说明**：开启后，方式 1 的**读取接口无需 Token**（仍需通过域名白名单）；写入接口始终需要 Token。方式 2 不受此选项影响（始终需要 Token）。

***

## 方式 1：域名白名单接口

### 基础路径

`/api/project/{project_identifier}/...`

### 验证机制

- 所有请求先经过 **域名白名单中间件**（`VerifyDomainWhitelist`）
- 读取（GET）：白名单通过后，若 `public_api = true` 直接放行；否则校验 Bearer Token（`read` 权限 + 属于该项目）
- 写入（POST / DELETE）：白名单通过后，校验 Bearer Token（`write` 权限 + 属于该项目）

***

## 方式 2：UUID + Token 接口

### 基础路径

`/api/{uuid}/...`

### 验证机制

**所有操作**：`auth:sanctum` Token 验证 + 项目 UUID 校验（Token 必须属于该 UUID 项目，且拥有对应权限）。防止任何网站跨域访问。

### UUID 说明

`{uuid}` 为项目 UUID（36 位），获取方式：后台 → 项目设置 → API Settings。

***

## 接口列表

### 方式 1：域名白名单接口

#### 📁 项目管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
| - | --- | --- | --- | --- | --- |
| 1 | GET | `/api/project/{project_identifier}` | 获取项目详情 | `project_identifier`: string（UUID 或 slug） | ✅ 白名单（+Token 若未开启 Public API） |

#### 📝 内容管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
| -- | --- | --- | --- | --- | --- |
| 2 | GET | `/api/project/{project_identifier}/{slug}` | 获取内容列表 | `where`、`whereRelation`、`sort`、`offset`、`limit`、`count`、`first`、`state`、`timestamps`（均可选） | ✅ 白名单（+Token 若未开启 Public API） |
| 3 | GET | `/api/project/{project_identifier}/{slug}/{slug_id}` | 获取单条内容 | `slug_id`: int、`timestamps`（可选） | ✅ 同上 |
| 3a | GET | `/api/project/{project_identifier}/{slug}/{slug_id}/{related_slug}` | 按关联内容查询（如分类下的文章） | `slug_id`: int、`related_slug`: string、同列表查询参数 | ✅ 同上 |
| 4 | POST | `/api/project/{project_identifier}/{slug}` | 创建内容 | Body: object（字段由集合定义） | ✅ 白名单 + Token（write） |
| 5 | POST | `/api/project/{project_identifier}/{slug}/update/{slug_id}` | 更新内容 | `slug_id`: int、Body: object | ✅ 白名单 + Token（write） |
| 6 | DELETE | `/api/project/{project_identifier}/{slug}/{slug_id}` | 删除内容 | `slug_id`: int | ✅ 白名单 + Token（write） |

#### 🖼️ 媒体库

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
| -- | --- | --- | --- | --- | --- |
| 7 | GET | `/api/project/{project_identifier}/media` | 获取媒体列表 | — | ✅ 白名单（+Token 若未开启 Public API） |
| 8 | GET | `/api/project/{project_identifier}/media/{media_id}` | 根据 ID 获取媒体 | `media_id`: int | ✅ 同上 |
| 9 | GET | `/api/project/{project_identifier}/media/name/{media_name}` | 根据名称获取媒体 | `media_name`: string | ✅ 同上 |
| 10 | POST | `/api/project/{project_identifier}/media/upload` | 上传媒体文件 | Form: `file`（大小受 `MAX_FILE_SIZE` 限制，默认 8M） | ✅ 白名单 + Token（write） |
| 11 | DELETE | `/api/project/{project_identifier}/media/{media_id}` | 删除媒体文件 | `media_id`: int | ✅ 白名单 + Token（write） |

### 方式 2：UUID + Token 接口

#### 📁 项目管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
| -- | --- | --- | --- | --- | --- |
| 12 | GET | `/api/{uuid}` | 获取项目详情 | `uuid`: string | ✅ UUID + Token |

#### 📝 内容管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
| -- | --- | --- | --- | --- | --- |
| 13 | GET | `/api/{uuid}/{slug}` | 获取内容列表 | 同列表查询参数 | ✅ UUID + Token |
| 14 | GET | `/api/{uuid}/{slug}/{slug_id}` | 获取单条内容 | `slug_id`: int、`timestamps`（可选） | ✅ UUID + Token |
| 14a | GET | `/api/{uuid}/{slug}/{slug_id}/{related_slug}` | 按关联内容查询 | 同列表查询参数 | ✅ UUID + Token |
| 15 | POST | `/api/{uuid}/{slug}` | 创建内容 | Body: object | ✅ UUID + Token（write） |
| 16 | POST | `/api/{uuid}/{slug}/update/{slug_id}` | 更新内容 | `slug_id`: int、Body: object | ✅ UUID + Token（write） |
| 17 | DELETE | `/api/{uuid}/{slug}/{slug_id}` | 删除内容 | `slug_id`: int | ✅ UUID + Token（write） |

#### 🖼️ 媒体库

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
| -- | --- | --- | --- | --- | --- |
| 18 | GET | `/api/{uuid}/project-media` | 获取媒体列表 | — | ✅ UUID + Token |
| 19 | GET | `/api/{uuid}/project-media/{media_id}` | 根据 ID 获取媒体 | `media_id`: int | ✅ UUID + Token |
| 20 | GET | `/api/{uuid}/project-media/name/{media_name}` | 根据名称获取媒体 | `media_name`: string | ✅ UUID + Token |
| 21 | POST | `/api/{uuid}/project-media/upload` | 上传媒体文件 | Form: `file` | ✅ UUID + Token（write） |
| 22 | DELETE | `/api/{uuid}/project-media/{media_id}` | 删除媒体文件 | `media_id`: int | ✅ UUID + Token（write） |

### 接口统计

| 类别 | 方式 1 | 方式 2 |
| --- | --- | --- |
| 项目接口 | 1 | 1 |
| 内容读取 | 3 | 3 |
| 内容写入 | 3 | 3 |
| 媒体读取 | 3 | 3 |
| 媒体写入 | 2 | 2 |
| **总计** | **12** | **12** |

***

## 参数说明

### 路径参数

| 参数 | 类型 | 说明 | 示例 |
| --- | --- | --- | --- |
| `{uuid}` | string | 项目的唯一标识符（36 位 UUID） | `abc123-def456-7890` |
| `{project_identifier}` | string | 项目标识符（UUID 或 slug） | `abc123-def456` 或 `my-blog` |
| `{slug}` | string | 内容集合的名称 | `articles`, `pages`, `listings` |
| `{slug_id}` | int | 内容的数字 ID | `1`, `123` |
| `{media_id}` | int | 媒体文件的数字 ID | `1`, `123` |
| `{media_name}` | string | 媒体文件的文件名 | `image.jpg` |
| `{related_slug}` | string | 关联集合的名称（用于关联查询） | `articles`, `listings` |

### 查询参数（内容列表）

| 参数 | 类型 | 必填 | 默认值 | 说明 |
| --- | --- | --- | --- | --- |
| `where` | object | 否 | - | 条件过滤，详见 [Where 条件](#where-条件) |
| `whereRelation` | object | 否 | - | 关联条件过滤，详见 [关联条件](#关联条件) |
| `sort` | string | 否 | - | 排序，格式 `field:direction`，支持逗号分隔多字段，如 `created_at:desc,title:asc` |
| `offset` | int | 否 | - | 偏移量，**必须与 `limit` 配合使用** |
| `limit` | int | 否 | - | 每页数量 |
| `count` | bool | 否 | false | 返回总数而非列表（`data` 为数字） |
| `first` | bool | 否 | false | 只返回第一条记录（`data` 为对象） |
| `state` | string | 否 | - | `only_draft` 仅返回草稿；**默认只返回已发布内容** |
| `timestamps` | bool | 否 | false | 是否返回 `created_at` / `updated_at` / `published_at` 字段 |

> **locale 过滤**：内容按语言存储，可通过 `where[locale]=zh` 过滤指定语言（`locale` 是内容列而非自定义字段）。

### 查询参数（单条内容）

| 参数 | 类型 | 必填 | 默认值 | 说明 |
| --- | --- | --- | --- | --- |
| `timestamps` | bool | 否 | false | 是否返回时间戳字段 |

> **注意**：单条内容接口同样默认只返回**已发布**内容——草稿通过该接口访问会返回 404。

### 请求体参数（创建 / 更新内容）

```json
{
    "title": "文章标题",
    "slug": "article-slug",
    "content": "文章内容",
    "locale": "zh",
    "draft": 0
}
```

| 字段 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `locale` | string | 否 | 语言，默认使用项目默认语言（如 `en` / `zh`） |
| `draft` | int | 否 | `1` 草稿（不设 `published_at`），`0` 发布；默认发布 |

> **注意**：其余字段由项目集合的自定义字段决定；字段校验（必填、唯一等）按集合字段配置执行。

### 请求头

**方式 1（跨域）**：

```
Origin: https://your-frontend.com
Authorization: Bearer your_api_token_here   # 未开启 Public API 的读取 & 所有写入
Content-Type: application/json              # POST/PUT 时
```

**方式 2**：

```
Authorization: Bearer your_api_token_here
Content-Type: application/json              # POST/PUT 时
```

***

## Where 条件

**基本格式**：

```json
{
    "field_name": "value"
}
```

**支持的操作**：

| 操作 | 格式 | 示例 |
| --- | --- | --- |
| 等于 | `"field": "value"` | `"title": "Hello"` |
| 不等于 | `"field": {"not": "value"}` | `"status": {"not": "draft"}` |
| 包含 | `"field": {"like": "pattern"}` | `"title": {"like": "%hello%"}` |
| 小于 | `"field": {"lt": "value"}` | `"price": {"lt": 100}` |
| 小于等于 | `"field": {"lte": "value"}` | `"price": {"lte": 100}` |
| 大于 | `"field": {"gt": "value"}` | `"price": {"gt": 10}` |
| 大于等于 | `"field": {"gte": "value"}` | `"price": {"gte": 10}` |
| 范围 | `"field": {"between": "min,max"}` | `"price": {"between": "10,100"}` |
| 不在范围 | `"field": {"not_between": "min,max"}` | `"price": {"not_between": "0,10"}` |
| 在列表中 | `"field": {"in": "val1,val2"}` | `"category": {"in": "news,blog"}` |
| 不在列表中 | `"field": {"not_in": "val1,val2"}` | `"category": {"not_in": "spam"}` |
| 为空 | `"field": "null"` | `"image": "null"` |
| 不为空 | `"field": "not_null"` | `"image": "not_null"` |

> 系统列（`id`、`locale`、`created_at`、`updated_at`、`published_at`）也可作为 where 条件；日期列支持 `lt`/`lte`/`gt`/`gte`（按日期比较）及 `between`/`in`/`not_in`/`not`。

**多条件（AND）**：

```json
{
    "status": "published",
    "category": "news"
}
```

**多条件（OR）**：

```json
{
    "or": [
        {"category": "news"},
        {"category": "blog"}
    ]
}
```

***

## 关联条件

**格式**：按关联字段过滤，关联字段的值是关联集合内容的 ID。

```json
{
    "relation_field": {
        "related_field": "value"
    }
}
```

**示例**（查询作者名字为 John 的文章）：

```json
{
    "author": {
        "name": "John"
    }
}
```

***

## 响应格式

### 成功响应

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": ...
}
```

- 列表：`data` 为数组
- 单条（`first=true` / 按 ID）：`data` 为对象
- 计数（`count=true`）：`data` 为数字
- 创建成功：HTTP 201，`message: "Content created successfully"`
- 删除成功：HTTP 200，`data: null`

### 内容对象结构

```json
{
    "id": 1,
    "locale": "zh",
    "title": "文章标题",
    "url": "article-slug",
    "category": "3",
    "featured-image": { "id": 5, "url": "/uploads/..." }
}
```

- **基础字段**：`id`、`locale`；`timestamps=true` 时含 `created_at` / `updated_at` / `published_at`
- **自定义字段**：以字段名为键展开（`title`、`url`、`category`…）
- **类型转换**：`boolean` 字段 → `true/false`；`number` 字段 → 数字；`media` 字段 → 媒体对象；`repeatable` 字段 → 数组
- **隐藏字段**：字段配置了 "Hidden in API" 时不会出现在响应中

***

## 错误响应

### 通用格式（API 控制器错误）

```json
{
    "success": false,
    "code": 400,
    "message": "错误描述",
    "data": null
}
```

### 中间件错误格式

```json
{
    "error": "Domain not in whitelist",
    "message": "Domain 'evil.com' is not in whitelist to access project 'my-blog'"
}
```

### 错误码说明

| 状态码 | 说明 |
| --- | --- |
| 400 | 请求参数错误（如 `offset` 未与 `limit` 搭配、where 格式错误） |
| 401 | 未认证（缺少 Token 或 Token 无效） |
| 403 | 无权限（Token 不属于该项目 / 权限不足 / 域名不在白名单） |
| 404 | 资源未找到（项目 / 集合 / 内容 / 媒体不存在） |
| 422 | 验证失败（创建/更新内容字段校验不通过、where 语句格式错误） |

### 常见错误

| 错误信息 | 原因 | 解决方案 |
| --- | --- | --- |
| `Missing project identifier` | 未提供项目标识符 | 在 URL 中提供 UUID 或 slug |
| `Project not found` | 项目标识符无效 | 检查 UUID 或 slug 是否正确 |
| `Domain not in whitelist` | 域名不在白名单中 | 在后台将域名加入 Domain Whitelist（需带协议） |
| `Unauthenticated` | 缺少 Token 或 Token 无效 | 添加正确的 `Authorization: Bearer` 头 |
| `API token is not valid for this project` | Token 不属于该项目 | 使用该项目的 Token |
| `API token does not have the required permissions` | Token 缺少 `read` / `write` 权限 | 在后台重新创建 Token 并勾选对应权限 |
| `Incorrect where statement` | where 参数格式错误 | 参考 [Where 条件](#where-条件) |
| `Incorrect offset statement. Offset must be used with limit` | offset 未与 limit 搭配 | 同时传 `limit` 参数 |

***

## 使用示例

### 方式 1：获取项目详情

```bash
curl -H "Origin: https://your-frontend.com" \
     https://backend.com/api/project/my-blog
```

### 方式 1：获取内容列表（Public API 已开启）

```bash
curl -H "Origin: https://your-frontend.com" \
     "https://backend.com/api/project/my-blog/articles?limit=10&sort=published_at:desc&where%5Blocale%5D=zh"
```

### 方式 1：获取内容列表（未开启 Public API，需 Token）

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-frontend.com" \
     "https://backend.com/api/project/my-blog/articles?limit=10&sort=created_at:desc&timestamps=true"
```

**响应**：

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "locale": "zh",
            "title": "人工智能的未来",
            "url": "ai-future",
            "category": "3",
            "created_at": "2026-01-15 10:30:00",
            "updated_at": "2026-01-15 10:30:00",
            "published_at": "2026-01-15 10:30:00"
        }
    ]
}
```

### 按关联内容查询（分类下的文章）

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-frontend.com" \
     "https://backend.com/api/project/my-blog/categories/3/articles?limit=10&sort=published_at:desc"
```

| 路径参数 | 值 | 说明 |
| --- | --- | --- |
| `slug` | `categories` | 源集合（分类） |
| `slug_id` | `3` | 分类 ID |
| `related_slug` | `articles` | 关联集合（文章） |

### 关联计数

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-frontend.com" \
     "https://backend.com/api/project/my-blog/authors/5/articles?count=true"
```

**响应**：

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": 25
}
```

### 使用 Where 条件

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-frontend.com" \
     "https://backend.com/api/project/my-blog/articles?where=%7B%22category%22%3A%223%22%2C%22status%22%3A%22published%22%7D"
```

### 方式 2：获取内容列表

```bash
curl -H "Authorization: Bearer your_token" \
     "https://backend.com/api/abc123-def456-7890/articles?limit=10&sort=created_at:desc"
```

### 创建内容

```bash
curl -X POST \
     -H "Authorization: Bearer your_token" \
     -H "Content-Type: application/json" \
     -H "Origin: https://your-frontend.com" \
     -d '{
         "title": "New Article",
         "url": "new-article",
         "content": "Article content",
         "locale": "en"
     }' \
     https://backend.com/api/project/my-blog/articles
```

**响应**（HTTP 201）：

```json
{
    "success": true,
    "code": 201,
    "message": "Content created successfully",
    "data": { "id": 42, "locale": "en", "title": "New Article", "url": "new-article" }
}
```

### 上传媒体

```bash
curl -X POST \
     -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-frontend.com" \
     -F "file=@image.jpg" \
     https://backend.com/api/project/my-blog/media/upload
```

### JavaScript 示例（Axios）

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'https://backend.com/api',
    headers: {
        'Authorization': 'Bearer your_token',
        'Origin': 'https://your-frontend.com'
    }
});

// 获取内容列表
const articles = await api.get('/project/my-blog/articles', {
    params: { limit: 10, sort: 'created_at:desc', where: { locale: 'zh' } }
});

// 创建内容
const newArticle = await api.post('/project/my-blog/articles', {
    title: 'New Article',
    url: 'new-article'
});

// 按分类查询
const news = await api.get('/project/my-blog/categories/3/articles');
```

***

## 快速开始

### 🚀 5 分钟集成（纯前端项目）

#### 第 1 步：配置后端

1. 登录后台管理
2. 进入项目设置 → API Settings
3. 添加域名到 Domain Whitelist：
   ```
   https://your-frontend.com
   http://localhost:3000
   ```
4. 可选：开启 Public API（开启后读取接口免 Token，适合完全公开的内容）
5. 需要写入时：在 Access Tokens 创建 Token（勾选 `read` / `write` 权限）

#### 第 2 步：前端调用

**React 示例**：

```jsx
import { useState, useEffect } from 'react';

function App() {
    const [articles, setArticles] = useState([]);

    useEffect(() => {
        fetch('https://backend.com/api/project/my-blog/articles?limit=10')
            .then(res => res.json())
            .then(data => setArticles(data.data));
    }, []);

    return (
        <div>
            {articles.map(article => (
                <div key={article.id}>{article.title}</div>
            ))}
        </div>
    );
}
```

**Vue 3 示例**：

```vue
<script setup>
import { ref, onMounted } from 'vue';

const articles = ref([]);

onMounted(async () => {
    const res = await fetch('https://backend.com/api/project/my-blog/articles?limit=10');
    const data = await res.json();
    articles.value = data.data;
});
</script>

<template>
    <div v-for="article in articles" :key="article.id">
        {{ article.title }}
    </div>
</template>
```

#### 第 3 步：测试

```bash
# 未开启 Public API 时需要 Token
curl -H "Authorization: Bearer your_token" \
     -H "Origin: http://localhost:3000" \
     https://backend.com/api/project/my-blog/articles
```

***

## 常见问题

### Q1: 两种方式可以同时使用吗？

**A**: ✅ 可以，两种方式互不影响，指向同一份数据。

### Q2: 方式 1 读取内容需要 Token 吗？

**A**:

- 项目开启了 **Public API**：❌ 不需要（但请求域名必须在白名单中）
- 项目未开启 Public API：✅ 需要（Token 需有 `read` 权限且属于该项目）

### Q3: 方式 2 有公开模式吗？

**A**: ❌ 没有。方式 2 的所有操作都必须带 Token，适合服务端调用。

### Q4: 如何保护敏感数据？

**A**:

- 关闭 Public API，所有读取都需 Token（`read` 权限）
- Token 只勾选最小必要权限（如只读就只勾 `read`）
- 白名单只添加可信域名

### Q5: 本地开发如何测试？

**A**:

- 前端项目：把本地地址加入 Domain Whitelist：
  ```
  http://localhost:3000
  http://127.0.0.1:3000
  ```
- 服务端调用：使用方式 2（UUID + Token），无域名限制
- 同源请求（CMS 自托管前端）无需配置白名单

### Q6: CORS 错误怎么办？

**A**: 确保：

1. 域名已添加到 Domain Whitelist（**需带协议**，如 `https://your-frontend.com`）
2. 浏览器请求携带 `Origin` 头（跨域请求浏览器会自动带）
3. 项目开启了 Public API 或请求携带了有效 Token

### Q7: 项目标识符支持哪些格式？

**A**:

- UUID：如 `abc123-def456-7890`
- Slug：如 `my-blog`（仅方式 1 支持 slug）

### Q8: 如何获取项目的 UUID 和 Slug？

**A**: 后台管理 → 项目设置 → API Settings 中查看。

### Q9: 如何按语言获取内容？

**A**: 使用 `where[locale]=zh`（或 `en` 等），例如：

```bash
curl "https://backend.com/api/project/my-blog/articles?where%5Blocale%5D=zh"
```

### Q10: 为什么列表接口默认看不到刚创建的草稿？

**A**: 列表接口和单条内容接口默认都只返回**已发布**内容（`published_at` 非空）。查看草稿需加 `state=only_draft`（仅列表接口支持；草稿无法通过单条接口按 ID 获取）。

***

**祝你集成顺利！** 🎉
