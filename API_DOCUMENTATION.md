# API 接口文档

## 📋 目录

1. [概述](#概述)
2. [认证方式](#认证方式)
3. [方式 1：UUID + Token](#方式-1uuid-token)
4. [方式 2：显式项目标识符](#方式-2显式项目标识符)
5. [接口列表](#接口列表)
6. [参数说明](#参数说明)
7. [错误响应](#错误响应)
8. [使用示例](#使用示例)
9. [快速开始](#快速开始)
10. [常见问题](#常见问题)

---

## 概述

本 CMS 提供 RESTful API 接口，支持两种认证方式：

### ✅ 方式 1：UUID + Token
- **适用场景**：Laravel 前端项目、后端服务器调用、需要写操作的场景
- **特点**：需要传递 UUID 和 Access Token
- **路由示例**：`/api/{uuid}/posts`

### ✅ 方式 2：显式项目标识符
- **适用场景**：纯前端项目（React/Vue/Angular 等）、多项目前端应用
- **特点**：必须传递项目标识符（UUID 或 slug），仅支持公开读取
- **路由示例**：`/api/project/my-project/posts`

---

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

---

## 方式 1：UUID + Token

### 基础路径
`/api/{uuid}/...`

### 适用场景
- Laravel 前端项目
- 后端服务器调用
- 需要写操作（POST/PUT/DELETE）
- 需要更高安全性的场景

---

## 方式 2：显式项目标识符

### 基础路径
`/api/project/{projectIdentifier}/...`

### 适用场景
- React / Vue / Angular 等纯前端项目
- 多项目前端应用（同一个前端域名访问多个后端项目）
- 仅读取公开内容的场景

### 项目标识符说明

`{projectIdentifier}` 支持两种格式：

| 格式 | 示例 | 获取方式 |
|------|------|---------|
| UUID | `abc123-def456-7890` | 后台 → 项目设置 → API Settings |
| Slug | `my-blog` | 后台 → 项目设置 → 基本信息 |

---

## 接口列表

### 方式 1：UUID + Token 接口

#### 📁 项目管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 1 | GET | `/api/{uuid}` | 获取项目详情 | `uuid`: string (项目UUID) | 公开或Token |

#### 📝 内容管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 2 | GET | `/api/{uuid}/{slug}` | 获取内容列表 | `uuid`: string<br>`slug`: string<br>`where`: object (可选)<br>`sort`: string (可选)<br>`offset`: int (可选)<br>`limit`: int (可选)<br>`count`: bool (可选)<br>`first`: bool (可选)<br>`state`: string (可选)<br>`timestamps`: bool (可选) | 公开或Token |
| 3 | GET | `/api/{uuid}/{slug}/{id}` | 获取单条内容 | `uuid`: string<br>`slug`: string<br>`id`: int<br>`timestamps`: bool (可选) | 公开或Token |
| 4 | POST | `/api/{uuid}/{slug}` | 创建内容 | `uuid`: string<br>`slug`: string<br>Body: object | ✅ Token |
| 5 | POST | `/api/{uuid}/{slug}/update/{id}` | 更新内容 | `uuid`: string<br>`slug`: string<br>`id`: int<br>Body: object | ✅ Token |
| 6 | DELETE | `/api/{uuid}/{slug}/{id}` | 删除内容 | `uuid`: string<br>`slug`: string<br>`id`: int | ✅ Token |

#### 🖼️ 媒体库

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 7 | GET | `/api/{uuid}/project-media` | 获取媒体列表 | `uuid`: string | 公开或Token |
| 8 | GET | `/api/{uuid}/project-media/{id}` | 根据ID获取媒体 | `uuid`: string<br>`id`: int | 公开或Token |
| 9 | GET | `/api/{uuid}/project-media/name/{name}` | 根据名称获取媒体 | `uuid`: string<br>`name`: string | 公开或Token |
| 10 | POST | `/api/{uuid}/project-media/upload` | 上传媒体文件 | `uuid`: string<br>Form: `file` | ✅ Token |
| 11 | DELETE | `/api/{uuid}/project-media/{id}` | 删除媒体文件 | `uuid`: string<br>`id`: int | ✅ Token |

---

### 方式 2：显式项目标识符接口

#### 📁 项目管理

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 12 | GET | `/api/project/{projectIdentifier}` | 获取项目详情 | `projectIdentifier`: string (UUID 或 slug) | 公开（需域名白名单） |

#### 📝 内容管理（只读）

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 13 | GET | `/api/project/{projectIdentifier}/{slug}` | 获取内容列表 | `projectIdentifier`: string<br>`slug`: string<br>`where`: object (可选)<br>`sort`: string (可选)<br>`offset`: int (可选)<br>`limit`: int (可选)<br>`count`: bool (可选)<br>`first`: bool (可选)<br>`state`: string (可选)<br>`timestamps`: bool (可选) | 公开（需域名白名单） |
| 14 | GET | `/api/project/{projectIdentifier}/{slug}/{id}` | 获取单条内容 | `projectIdentifier`: string<br>`slug`: string<br>`id`: int<br>`timestamps`: bool (可选) | 公开（需域名白名单） |

#### 🖼️ 媒体库（只读）

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 15 | GET | `/api/project/{projectIdentifier}/media` | 获取媒体列表 | `projectIdentifier`: string | 公开（需域名白名单） |
| 16 | GET | `/api/project/{projectIdentifier}/media/{id}` | 根据ID获取媒体 | `projectIdentifier`: string<br>`id`: int | 公开（需域名白名单） |

---

### 接口统计

| 类别 | 方式 1 (UUID) | 方式 2 (显式标识符) |
|------|--------------|---------------------|
| **项目接口** | 1 | 1 |
| **内容读取** | 2 | 2 |
| **内容写入** | 3 | 0 |
| **媒体读取** | 3 | 2 |
| **媒体写入** | 2 | 0 |
| **总计** | **11** | **5** |

---

## 参数说明

### 路径参数

| 参数 | 类型 | 说明 | 示例 |
|------|------|------|------|
| `{uuid}` | string | 项目的唯一标识符（36位UUID） | `abc123-def456-7890` |
| `{projectIdentifier}` | string | 项目标识符（UUID 或 slug） | `abc123-def456` 或 `my-blog` |
| `{slug}` | string | 内容集合的名称 | `posts`, `pages`, `products` |
| `{id}` | int | 内容或媒体的数字ID | `1`, `123` |
| `{name}` | string | 媒体文件的文件名 | `image.jpg` |

### 查询参数

#### 获取内容列表

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `where` | object | 否 | - | 条件过滤，详见 [Where 条件](#where-条件) |
| `whereRelation` | object | 否 | - | 关联条件过滤，详见 [关联条件](#关联条件) |
| `sort` | string | 否 | - | 排序，格式：`field:direction`，如 `created_at:desc` |
| `offset` | int | 否 | - | 偏移量，需与 `limit` 配合使用 |
| `limit` | int | 否 | - | 每页数量 |
| `count` | bool | 否 | false | 返回总数而非列表 |
| `first` | bool | 否 | false | 返回第一条记录 |
| `state` | string | 否 | - | 状态筛选，`only_draft` 表示仅草稿 |
| `timestamps` | bool | 否 | false | 是否返回时间戳字段 |

#### 获取单条内容

| 参数 | 类型 | 必填 | 默认值 | 说明 |
|------|------|------|--------|------|
| `timestamps` | bool | 否 | false | 是否返回时间戳字段 |

### Where 条件

**基本格式**：
```json
{
    "field_name": "value"
}
```

**支持的操作**：

| 操作 | 格式 | 示例 |
|------|------|------|
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

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `title` | string | 否 | 标题（根据集合字段定义） |
| `slug` | string | 否 | 别名 |
| `locale` | string | 否 | 语言，默认项目默认语言 |
| `draft` | int | 否 | 是否草稿，`1` 草稿，`0` 发布 |

> **注意**：实际字段根据项目集合的自定义字段而定

#### 上传媒体

| 字段 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `file` | file | 是 | 二进制文件 |

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

---

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

| 状态码 | 错误码 | 说明 |
|--------|--------|------|
| 400 | - | 请求参数错误 |
| 401 | - | 未认证（缺少 Token） |
| 403 | - | 无权限（Token 无效或域名不在白名单） |
| 404 | - | 资源未找到 |
| 422 | - | 验证失败 |

### 常见错误

| 错误信息 | 原因 | 解决方案 |
|----------|------|---------|
| `Missing project identifier` | 未提供项目标识符 | 在 URL 中添加 `projectIdentifier` |
| `Project not found` | 项目标识符无效 | 检查 UUID 或 slug 是否正确 |
| `Domain not in whitelist` | 域名不在白名单中 | 在后台添加域名到 Domain Whitelist |
| `Unauthenticated` | 缺少 Token | 添加 Authorization 头 |
| `API token is not valid for this project` | Token 不属于该项目 | 使用正确项目的 Token |

---

## 使用示例

### 方式 1：UUID + Token

#### 获取项目详情

**请求**：
```bash
curl -H "Authorization: Bearer your_token" \
     -H "Origin: https://your-domain.com" \
     https://backend.com/api/abc123-def456
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

---

### 方式 2：显式项目标识符

#### 获取项目详情

**请求**：
```bash
curl -H "Origin: https://your-domain.com" \
     https://backend.com/api/project/my-project
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

---

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

---

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

---

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

---

**祝你集成顺利！** 🎉