# API 接口文档

## 📋 目录

1. [概述](#概述)
2. [认证方式](#认证方式)
3. [方式 1：域名白名单接口](#方式-1域名白名单接口)
4. [方式 2：UUID + Token 接口](#方式-2uuid-token接口)
5. [接口列表](#接口列表)
6. [参数说明](#参数说明)
7. [错误响应](#错误响应)
8. [使用示例](#使用示例)
9. [快速开始](#快速开始)
10. [常见问题](#常见问题)

***

## 概述

本 CMS 提供 RESTful API 接口，支持两种认证方式（**均不可公开访问**）：

### ✅ 方式 1：域名白名单接口（纯前端项目）

- **适用场景**：Vue、React等纯前端项目，无法在前端代码中隐藏Token
- **特点**：通过配置域名白名单实现跨域访问，读取操作仅验证域名白名单，写入操作需额外Token验证
- **路由示例**：`/api/project/my-blog/posts`

### ✅ 方式 2：UUID + Token 接口（后端项目）

- **适用场景**：Laravel、Java等后端项目，可以在服务端隐藏Token
- **特点**：所有操作都需要UUID验证 + Token验证（双验证），防止任何网站跨域访问
- **路由示例**：`/api/abc123-def456/posts`

***

## 认证方式

### 1. Bearer Token 认证

**请求头**：

```
Authorization: Bearer {your_access_token}
```

**获取方式**：在后台管理 → 项目设置 → API Settings → Access Tokens 创建

### 2. 域名白名单验证

**请求头**：

```
Origin: https://your-frontend.com
```

**配置方式**：在后台管理 → 项目设置 → API Settings → Domain Whitelist 添加域名

**作用**：验证请求来源域名是否在项目的允许列表中，用于防止未授权的跨域访问

### 3. 公开 API

**条件**：项目开启 `public_api` 选项

**说明**：公开接口无需 Token，但仍需域名在白名单中（方式 2）

***

## 方式 1：域名白名单接口

### 基础路径

`/api/project/{project_identifier}/...`

### 适用场景

- Vue、React等纯前端项目
- 无法在前端代码中隐藏Token的场景
- 通过配置域名白名单实现跨域访问

### 验证机制

- **读取操作（GET）**：验证请求域名是否在项目白名单中
- **写入操作（POST/DELETE）**：域名白名单验证 + Token验证

***

## 方式 2：UUID + Token 接口

### 基础路径

`/api/{uuid}/...`

### 适用场景

- Laravel、Java等后端项目
- 可以在服务端隐藏Token的场景
- 需要更高安全性的场景（双验证）

### 验证机制

**所有操作**：UUID验证 + Token验证（双验证），防止任何网站跨域访问

### UUID说明

`{uuid}` 为项目UUID，获取方式：后台 → 项目设置 → API Settings

***

## 接口列表

### 方式 1：域名白名单接口（纯前端项目）

**适用场景**：Vue、React等纯前端项目，无法在前端代码中隐藏Token，通过配置域名白名单实现跨域访问。

**验证机制**：
- 读取操作：验证请求域名是否在项目白名单中
- 写入操作：域名白名单验证 + Token验证

#### 📁 项目管理

| # | 方法  | 接口路径                               | 说明     | 参数                                        | 认证                   |
| - | --- | ---------------------------------- | ------ | ----------------------------------------- | -------------------- |
| 1 | GET | `/api/project/{project_identifier}` | 获取项目详情<br>示例：`/api/project/my-blog` | `project_identifier`: string (UUID 或 slug) | ✅ 域名白名单验证 |

#### 📝 内容管理

| #  | 方法     | 接口路径                                           | 说明               | 参数                                                                                                                                                                                                 | 认证                   |
| -- | ------ | ---------------------------------------------- | ---------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------- |
| 2  | GET    | `/api/project/{project_identifier}/{slug}`          | 获取内容列表<br>示例：`/api/project/my-blog/posts` | `project_identifier`: string`slug`: string`where`: object (可选)`sort`: string (可选)`offset`: int (可选)`limit`: int (可选)`count`: bool (可选)`first`: bool (可选)`state`: string (可选)`timestamps`: bool (可选) | ✅ 域名白名单验证 |
| 3  | GET    | `/api/project/{project_identifier}/{slug}/{slug_id}` | 获取单条内容<br>示例：`/api/project/my-blog/posts/1` | `project_identifier`: string`slug`: string`slug_id`: int`timestamps`: bool (可选)                                                                                                                          | ✅ 域名白名单验证 |
| 3a | GET    | `/api/project/{project_identifier}/{slug}/{slug_id}/{related_slug}` | 按关联内容查询（如分类下的文章）<br>示例：`/api/project/my-blog/categories/3/posts` | `project_identifier`: string`slug`: string`slug_id`: int`related_slug`: string`sort`: string (可选)`offset`: int (可选)`limit`: int (可选)`count`: bool (可选)`first`: bool (可选)`state`: string (可选)`timestamps`: bool (可选) | ✅ 域名白名单验证 |
| 4  | POST   | `/api/project/{project_identifier}/{slug}`          | 创建内容<br>示例：`/api/project/my-blog/posts` | `project_identifier`: string`slug`: stringBody: object                                                                                                                                                                      | ✅ 域名白名单 + Token |
| 5  | POST   | `/api/project/{project_identifier}/{slug}/update/{slug_id}` | 更新内容<br>示例：`/api/project/my-blog/posts/update/1` | `project_identifier`: string`slug`: string`slug_id`: intBody: object                                                                                                                                                             | ✅ 域名白名单 + Token |
| 6  | DELETE | `/api/project/{project_identifier}/{slug}/{slug_id}` | 删除内容<br>示例：`/api/project/my-blog/posts/1` | `project_identifier`: string`slug`: string`slug_id`: int                                                                                                                                                                         | ✅ 域名白名单 + Token |

#### 🖼️ 媒体库

| #  | 方法     | 接口路径                                          | 说明       | 参数                                   | 认证                   |
| -- | ------ | --------------------------------------------- | -------- | ------------------------------------ | -------------------- |
| 7  | GET    | `/api/project/{project_identifier}/media`          | 获取媒体列表<br>示例：`/api/project/my-blog/media` | `project_identifier`: string          | ✅ 域名白名单验证 |
| 8  | GET    | `/api/project/{project_identifier}/media/{media_id}` | 根据ID获取媒体<br>示例：`/api/project/my-blog/media/1` | `project_identifier`: string`media_id`: int | ✅ 域名白名单验证 |
| 9  | GET    | `/api/project/{project_identifier}/media/name/{media_name}` | 根据名称获取媒体<br>示例：`/api/project/my-blog/media/name/image.jpg` | `project_identifier`: string`media_name`: string | ✅ 域名白名单验证 |
| 10 | POST   | `/api/project/{project_identifier}/media/upload`    | 上传媒体文件<br>示例：`/api/project/my-blog/media/upload` | `project_identifier`: stringForm: `file` | ✅ 域名白名单 + Token |
| 11 | DELETE | `/api/project/{project_identifier}/media/{media_id}` | 删除媒体文件<br>示例：`/api/project/my-blog/media/1` | `project_identifier`: string`media_id`: int | ✅ 域名白名单 + Token |

***

### 方式 2：UUID + Token 接口（后端项目）

**适用场景**：Laravel、Java等后端项目，可以在服务端隐藏Token，通过UUID+Token实现跨域访问。

**验证机制**：所有操作都需要 UUID验证 + Token验证（双验证），防止任何网站跨域访问。

#### 📁 项目管理

| #  | 方法  | 接口路径          | 说明     | 参数                      | 认证                 |
| -- | --- | ------------- | ------ | ----------------------- | ------------------ |
| 12 | GET | `/api/{uuid}` | 获取项目详情 | `uuid`: string (项目UUID) | ✅ UUID + Token |

#### 📝 内容管理

| #  | 方法     | 接口路径                                                  | 说明               | 参数                                                                                                                                                                                                            | 认证                 |
| -- | ------ | ----------------------------------------------------- | ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------ |
| 13 | GET    | `/api/{uuid}/{slug}`                                  | 获取内容列表<br>示例：`/api/abc123/posts` | `uuid`: string`slug`: string`where`: object (可选)`sort`: string (可选)`offset`: int (可选)`limit`: int (可选)`count`: bool (可选)`first`: bool (可选)`state`: string (可选)`timestamps`: bool (可选)                         | ✅ UUID + Token |
| 14 | GET    | `/api/{uuid}/{slug}/{slug_id}`                     | 获取单条内容<br>示例：`/api/abc123/posts/1` | `uuid`: string`slug`: string`slug_id`: int`timestamps`: bool (可选)                                                                                                                                                  | ✅ UUID + Token |
| 14a | GET    | `/api/{uuid}/{slug}/{slug_id}/{related_slug}` | 按关联内容查询（如分类下的文章）<br>示例：`/api/abc123/categories/3/posts` | `uuid`: string`slug`: string`slug_id`: int`related_slug`: string`sort`: string (可选)`offset`: int (可选)`limit`: int (可选)`count`: bool (可选)`first`: bool (可选)`state`: string (可选)`timestamps`: bool (可选) | ✅ UUID + Token |
| 15 | POST   | `/api/{uuid}/{slug}`                                  | 创建内容<br>示例：`/api/abc123/posts` | `uuid`: string`slug`: stringBody: object                                                                                                                                                                      | ✅ UUID + Token |
| 16 | POST   | `/api/{uuid}/{slug}/update/{slug_id}`              | 更新内容<br>示例：`/api/abc123/posts/update/1` | `uuid`: string`slug`: string`slug_id`: intBody: object                                                                                                                                                             | ✅ UUID + Token |
| 17 | DELETE | `/api/{uuid}/{slug}/{slug_id}`                     | 删除内容<br>示例：`/api/abc123/posts/1` | `uuid`: string`slug`: string`slug_id`: int                                                                                                                                                                         | ✅ UUID + Token |

#### 🖼️ 媒体库

| #  | 方法     | 接口路径                                    | 说明       | 参数                           | 认证                 |
| -- | ------ | --------------------------------------- | -------- | ---------------------------- | ------------------ |
| 18 | GET    | `/api/{uuid}/project-media`                 | 获取媒体列表<br>示例：`/api/abc123/project-media` | `uuid`: string                     | ✅ UUID + Token |
| 19 | GET    | `/api/{uuid}/project-media/{media_id}`      | 根据ID获取媒体<br>示例：`/api/abc123/project-media/1` | `uuid`: string`media_id`: int      | ✅ UUID + Token |
| 20 | GET    | `/api/{uuid}/project-media/name/{media_name}` | 根据名称获取媒体<br>示例：`/api/abc123/project-media/name/image.jpg` | `uuid`: string`media_name`: string | ✅ UUID + Token |
| 21 | POST   | `/api/{uuid}/project-media/upload`          | 上传媒体文件<br>示例：`/api/abc123/project-media/upload` | `uuid`: stringForm: `file`         | ✅ UUID + Token |
| 22 | DELETE | `/api/{uuid}/project-media/{media_id}`      | 删除媒体文件<br>示例：`/api/abc123/project-media/1` | `uuid`: string`media_id`: int      | ✅ UUID + Token |

***

### 接口统计

| 类别       | 方式 1 (显式标识符) | 方式 2 (UUID) |
| -------- | ------------ | ----------- |
| **项目接口** | 1            | 1           |
| **内容读取** | 3            | 3           |
| **内容写入** | 3            | 3           |
| **媒体读取** | 3            | 3           |
| **媒体写入** | 2            | 2           |
| **总计**   | **12**       | **12**      |

***

## 参数说明

### 路径参数

| 参数                    | 类型     | 说明                 | 示例                              |
| --------------------- | ------ | ------------------ | ------------------------------- |
| `{uuid}`              | string | 项目的唯一标识符（36位UUID）  | `abc123-def456-7890`            |
| `{project_identifier}` | string | 项目标识符（UUID 或 slug） | `abc123-def456` 或 `my-blog`     |
| `{slug}`              | string | 内容集合的名称            | `posts`, `pages`, `products`    |
| `{slug_id}`        | int    | 内容的数字ID              | `1`, `123`                      |
| `{media_id}`          | int    | 媒体文件的数字ID           | `1`, `123`                      |
| `{media_name}`        | string | 媒体文件的文件名           | `image.jpg`                     |
| `{related_slug}`      | string | 关联集合的名称（用于关联查询）    | `posts`, `articles`             |

### 查询参数

#### 获取内容列表

| 参数              | 类型     | 必填 | 默认值   | 说明                                          |
| --------------- | ------ | -- | ----- | ------------------------------------------- |
| `where`         | object | 否  | -     | 条件过滤，详见 [Where 条件](#where-条件)               |
| `whereRelation` | object | 否  | -     | 关联条件过滤，详见 [关联条件](#关联条件)                     |
| `sort`          | string | 否  | -     | 排序，格式：`field:direction`，如 `created_at:desc` |
| `offset`        | int    | 否  | -     | 偏移量，需与 `limit` 配合使用                         |
| `limit`         | int    | 否  | -     | 每页数量                                        |
| `count`         | bool   | 否  | false | 返回总数而非列表                                    |
| `first`         | bool   | 否  | false | 返回第一条记录                                     |
| `state`         | string | 否  | -     | 状态筛选，`only_draft` 表示仅草稿                     |
| `timestamps`    | bool   | 否  | false | 是否返回时间戳字段                                   |

#### 获取单条内容

| 参数           | 类型   | 必填 | 默认值   | 说明        |
| ------------ | ---- | -- | ----- | --------- |
| `timestamps` | bool | 否  | false | 是否返回时间戳字段 |

### Where 条件

**基本格式**：

```json
{
    "field_name": "value"
}
```

**支持的操作**：

| 操作    | 格式                                    | 示例                                 |
| ----- | ------------------------------------- | ---------------------------------- |
| 等于    | `"field": "value"`                    | `"title": "Hello"`                 |
| 不等于   | `"field": {"not": "value"}`           | `"status": {"not": "draft"}`       |
| 包含    | `"field": {"like": "pattern"}`        | `"title": {"like": "%hello%"}`     |
| 小于    | `"field": {"lt": "value"}`            | `"price": {"lt": 100}`             |
| 小于等于  | `"field": {"lte": "value"}`           | `"price": {"lte": 100}`            |
| 大于    | `"field": {"gt": "value"}`            | `"price": {"gt": 10}`              |
| 大于等于  | `"field": {"gte": "value"}`           | `"price": {"gte": 10}`             |
| 范围    | `"field": {"between": "min,max"}`     | `"price": {"between": "10,100"}`   |
| 不在范围  | `"field": {"not_between": "min,max"}` | `"price": {"not_between": "0,10"}` |
| 在列表中  | `"field": {"in": "val1,val2"}`        | `"category": {"in": "news,blog"}`  |
| 不在列表中 | `"field": {"not_in": "val1,val2"}`    | `"category": {"not_in": "spam"}`   |
| 为空    | `"field": "null"`                     | `"image": "null"`                  |
| 不为空   | `"field": "not_null"`                 | `"image": "not_null"`              |

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

### 关联条件

**格式**：

```json
{
    "relation_field": {
        "related_field": "value"
    }
}
```

**示例**：

```json
{
    "author": {
        "name": "John"
    }
}
```

### 请求体参数

#### 创建/更新内容

```json
{
    "title": "文章标题",
    "slug": "article-slug",
    "content": "文章内容",
    "locale": "zh-CN",
    "draft": 0
}
```

| 字段       | 类型     | 必填 | 说明                 |
| -------- | ------ | -- | ------------------ |
| `title`  | string | 否  | 标题（根据集合字段定义）       |
| `slug`   | string | 否  | 别名                 |
| `locale` | string | 否  | 语言，默认项目默认语言        |
| `draft`  | int    | 否  | 是否草稿，`1` 草稿，`0` 发布 |

> **注意**：实际字段根据项目集合的自定义字段而定

#### 上传媒体

| 字段     | 类型   | 必填 | 说明    |
| ------ | ---- | -- | ----- |
| `file` | file | 是  | 二进制文件 |

### 请求头

#### 需要认证的接口

```
Authorization: Bearer your_api_token_here
Content-Type: application/json
Origin: https://your-domain.com
```

#### 公开接口（方式 2）

```
Origin: https://your-domain.com
```

***

## 错误响应

### 通用格式

```json
{
    "success": false,
    "code": 400,
    "message": "错误描述",
    "data": null
}
```

### 错误码说明

| 状态码 | 错误码 | 说明                    |
| --- | --- | --------------------- |
| 400 | -   | 请求参数错误                |
| 401 | -   | 未认证（缺少 Token）         |
| 403 | -   | 无权限（Token 无效或域名不在白名单） |
| 404 | -   | 资源未找到                 |
| 422 | -   | 验证失败                  |

### 常见错误

| 错误信息                                      | 原因           | 解决方案                          |
| ----------------------------------------- | ------------ | ----------------------------- |
| `Missing project identifier`              | 未提供项目标识符     | 在 URL 中添加 `projectIdentifier` |
| `Project not found`                       | 项目标识符无效      | 检查 UUID 或 slug 是否正确           |
| `Domain not in whitelist`                 | 域名不在白名单中     | 在后台添加域名到 Domain Whitelist     |
| `Unauthenticated`                         | 缺少 Token     | 添加 Authorization 头            |
| `API token is not valid for this project` | Token 不属于该项目 | 使用正确项目的 Token                 |

***

## 使用示例

### 方式 1：域名白名单接口

#### 获取项目详情

**请求**：

```bash
curl -H "Origin: https://your-domain.com" \
     https://backend.com/api/project/my-blog
```

**说明**：请求域名 `https://your-domain.com` 必须在项目白名单中

#### 获取内容列表

**请求**：

```bash
curl -H "Origin: https://your-domain.com" \
     https://backend.com/api/project/my-blog/posts
```

**响应**：

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": {
        "id": 1,
        "uuid": "abc123-def456",
        "name": "My Project",
        "slug": "my-project",
        "public_api": true
    }
}
```

#### 获取内容列表

**请求**：

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-domain.com" \
     "https://backend.com/api/abc123-def456/posts?limit=10&sort=created_at:desc"
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
            "project_id": 1,
            "collection_id": 2,
            "locale": "zh-CN",
            "meta": {...}
        }
    ]
}
```

#### 按关联内容查询（分类下的文章）

**场景说明**：查询某个分类下的所有文章，适用于"科技分类下的文章列表"等场景。

**请求**：

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-domain.com" \
     "https://backend.com/api/abc123-def456/categories/3/posts?limit=10&sort=created_at:desc&timestamps=true"
```

**路径参数说明**：

| 参数          | 值            | 说明          |
| ----------- | ------------ | ----------- |
| `slug`       | `categories` | 源集合（分类）     |
| `slug_id` | `3`          | 分类ID        |
| `related_slug` | `posts`      | 关联集合（文章）    |

**响应**：

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "project_id": 1,
            "collection_id": 2,
            "locale": "zh-CN",
            "created_at": "2024-01-15T10:30:00Z",
            "updated_at": "2024-01-15T10:30:00Z",
            "published_at": "2024-01-15T10:30:00Z",
            "meta": {
                "title": "人工智能的未来",
                "url": "ai-future",
                "category": "3",
                "author": "1"
            }
        }
    ]
}
```

#### 按关联内容查询（作者的文章）

**请求**：

```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-domain.com" \
     "https://backend.com/api/abc123-def456/authors/5/posts?count=true"
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

#### 创建内容

**请求**：

```bash
curl -X POST \
     -H "Authorization: Bearer your_token" \
     -H "Content-Type: application/json" \
     -H "Origin: https://your-domain.com" \
     -d '{
         "title": "New Post",
         "slug": "new-post",
         "content": "Post content"
     }' \
     https://backend.com/api/abc123-def456/posts
```

**响应**：

```json
{
    "success": true,
    "code": 201,
    "message": "Content created successfully",
    "data": {...}
}
```

#### 上传媒体

**请求**：

```bash
curl -X POST \
     -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-domain.com" \
     -F "file=@image.jpg" \
     https://backend.com/api/abc123-def456/project-media/upload
```

***

### 方式 2：UUID + Token 接口

#### 获取项目详情

**请求**：

```bash
curl -H "Authorization: Bearer your_token" \
     https://backend.com/api/abc123-def456
```

**说明**：所有操作都需要 UUID + Token 双验证

#### 获取内容列表

**请求**：

```bash
curl -H "Authorization: Bearer your_token" \
     https://backend.com/api/abc123-def456/posts
```

**响应**：

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": {
        "id": 1,
        "uuid": "abc123-def456",
        "name": "My Project",
        "slug": "my-project",
        "public_api": true
    }
}
```

#### 获取内容列表

**请求**：

```bash
curl -H "Origin: https://your-domain.com" \
     "https://backend.com/api/project/my-project/posts?limit=10"
```

**响应**：

```json
{
    "success": true,
    "code": 200,
    "message": "Success",
    "data": [...]
}
```

#### 使用 Where 条件

**请求**：

```bash
curl -H "Origin: https://your-domain.com" \
     "https://backend.com/api/project/my-project/posts?where={\"category\":\"news\",\"status\":\"published\"}"
```

#### 获取媒体列表

**请求**：

```bash
curl -H "Origin: https://your-domain.com" \
     https://backend.com/api/project/my-project/media
```

***

### JavaScript 示例

#### Axios（方式 1）

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'https://backend.com/api',
    headers: {
        'Authorization': 'Bearer your_token',
        'Origin': 'https://your-domain.com'
    }
});

// 获取内容列表
const posts = await api.get('/abc123-def456/posts', {
    params: {
        limit: 10,
        sort: 'created_at:desc'
    }
});

// 创建内容
const newPost = await api.post('/abc123-def456/posts', {
    title: 'New Post',
    slug: 'new-post'
});
```

#### Axios（方式 2）

```javascript
import axios from 'axios';

const api = axios.create({
    baseURL: 'https://backend.com/api/project',
    headers: {
        'Origin': 'https://your-domain.com'
    }
});

// 获取项目1的内容
const blogPosts = await api.get('/my-blog/posts');

// 获取项目2的内容
const shopProducts = await api.get('/my-shop/products');
```

***

## 快速开始

### 🚀 5 分钟集成（方式 2）

#### 第 1 步：配置后端

1. 登录后台管理
2. 进入项目设置 → API Settings
3. 添加 API Allowed Domains：
   ```
   https://your-frontend.com
   http://localhost:3000
   ```
4. 启用 Public API：✅

#### 第 2 步：前端调用

**React 示例**：

```jsx
import { useState, useEffect } from 'react';

function App() {
    const [posts, setPosts] = useState([]);

    useEffect(() => {
        fetch('https://backend.com/api/project/my-blog/posts')
            .then(res => res.json())
            .then(data => setPosts(data.data));
    }, []);

    return (
        <div>
            {posts.map(post => (
                <div key={post.id}>{post.title}</div>
            ))}
        </div>
    );
}
```

**Vue 3 示例**：

```vue
<script setup>
import { ref, onMounted } from 'vue';

const posts = ref([]);

onMounted(async () => {
    const res = await fetch('https://backend.com/api/project/my-blog/posts');
    const data = await res.json();
    posts.value = data.data;
});
</script>

<template>
    <div v-for="post in posts" :key="post.id">
        {{ post.title }}
    </div>
</template>
```

#### 第 3 步：测试

```bash
curl -H "Origin: http://localhost:3000" \
     https://backend.com/api/project/my-blog/posts
```

***

## 常见问题

### Q1: 两种方式可以同时使用吗？

**A**: ✅ 可以！两种方式互不影响。

### Q2: 方式 2 需要 Token 吗？

**A**:

- 读取公开内容：❌ 不需要
- 写入内容（POST/PUT/DELETE）：✅ 需要（使用方式 1）

### Q3: 如何保护敏感数据？

**A**:

- 方式 1：使用 Token + 关闭 Public API
- 方式 2：只配置可信域名 + 关闭 Public API

### Q4: 本地开发如何测试？

**A**: 添加本地域名到 API Allowed Domains：

```
http://localhost:3000
http://127.0.0.1:3000
```

### Q5: CORS 错误怎么办？

**A**: 确保：

1. 域名已添加到 API Allowed Domains
2. 请求中包含正确的 Origin 头
3. 后端 CORS 配置正确（`config/cors.php`）

### Q6: 项目标识符支持哪些格式？

**A**:

- UUID：如 `abc123-def456-7890`
- Slug：如 `my-blog`

### Q7: 如何获取项目的 UUID 和 Slug？

**A**: 在后台管理 → 项目设置 → API Settings 中查看。

***

**祝你集成顺利！** 🎉路径参数说明

```markdown
```

