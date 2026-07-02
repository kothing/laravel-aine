# API 调用方式完整指南

## 📋 目录

1. [概述](#概述)
2. [方式 1：UUID + Token](#方式-1uuid-token原有方式)
3. [方式 2：域名自动解析](#方式-2域名自动解析新增方式)
4. [两种方式对比](#两种方式对比)
5. [快速开始](#快速开始)
6. [常见问题](#常见问题)

---

## 概述

本 CMS 支持**两种 API 调用方式**，适用于不同的使用场景：

### ✅ 方式 1：UUID + Token
- **适用场景**：Laravel 前端项目、后端服务器调用
- **特点**：需要传递 UUID 和 Access Token
- **路由示例**：`/api/{uuid}/posts`

### ✅ 方式 2：域名自动解析
- **适用场景**：纯前端项目（React/Vue/Angular 等）
- **特点**：配置 API Allowed Domains 后，后端根据 Origin 自动解析项目
- **路由示例**：`/api/project/posts`

### 📌 什么是 API Allowed Domains？

**API Allowed Domains（API 允许调用域名）** 是本项目中允许调用当前项目 API 的**客户端应用域名白名单**。

- 不是「前端网站托管在哪」的泛称，而是「谁被授权来调 API」
- 浏览器跨域请求时，配合 **Origin** 头做 CORS 与域名校验
- 与 **Access Token** 配合使用：非公开 API 必须同时满足「域名在白名单内」+「有效 Token」
- 后台路径：**项目设置 → API Settings → API Allowed Domains**

---

## 方式 1：UUID + Token

### 🎯 适用场景

✅ Laravel 前端项目  
✅ 后端服务器调用  
✅ 需要明确指定项目的场景  
✅ 多项目混合调用的场景  

### 🔧 工作原理

```
┌─────────────────────┐         ┌──────────────────┐
│   Laravel 前端       │         │  Laravel Backend  │
│                     │         │                  │
│  请求:               │         │  1. 验证 Origin   │
│  GET /api/abc123    │────────▶│  2. 检查白名单    │
│  /posts             │         │  3. 验证 Token    │
│                     │         │  4. 返回数据      │
│  Headers:           │         │                  │
│  Authorization:     │         │                  │
│  Bearer {token}     │         │                  │
└─────────────────────┘         └──────────────────┘
```

### 📝 使用步骤

#### 1. 获取项目 UUID

在后台管理界面找到你的项目，复制 UUID。

```
项目设置 → API Settings → Content API Endpoint
例如: https://backend.com/api/abc123-def456
                             ↑↑↑↑↑↑↑↑↑↑↑↑↑↑
                             这是 UUID
```

#### 2. 创建 Access Token

```bash
# 在后台管理界面
项目设置 → API Settings → Access Tokens → Create New Token
```

保存 Token（只显示一次）：
```
your_api_token_here_123456789
```

#### 3. 配置 API Allowed Domains（可选但推荐）

```bash
# 在后台管理界面
项目设置 → API Settings → API Allowed Domains
添加: https://your-frontend.com
```

#### 4. 发起 API 请求

**PHP (Laravel 前端)**：
```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer your_api_token_here_123456789',
    'Origin' => 'https://your-frontend.com'
])->get('https://backend.com/api/abc123-def456/posts');

$posts = $response->json();
```

**JavaScript (Axios)**：
```javascript
import axios from 'axios';

const response = await axios.get(
    'https://backend.com/api/abc123-def456/posts',
    {
        headers: {
            'Authorization': 'Bearer your_api_token_here_123456789',
            'Origin': 'https://your-frontend.com'
        }
    }
);

console.log(response.data);
```

### 🔐 认证要求

| 端点 | 公开 API | 需要 Token |
|------|---------|-----------|
| `GET /{uuid}/{slug}` | ✅ 如果 public_api=1 | ❌ |
| `GET /{uuid}/{slug}/{id}` | ✅ 如果 public_api=1 | ❌ |
| `POST /{uuid}/{slug}` | ❌ | ✅ |
| `DELETE /{uuid}/{slug}/{id}` | ❌ | ✅ |

### ⚠️ 注意事项

1. **Token 安全**：不要在前端代码中硬编码 Token
2. **Origin 头**：如果配置了 API Allowed Domains，必须提供正确的 Origin
3. **UUID 保密**：UUID 相当于项目 ID，不要泄露

---

## 方式 2：域名自动解析

### 🎯 适用场景

✅ React / Vue / Angular 等纯前端项目  
✅ 不想在代码中暴露 UUID 和 Token  
✅ 单项目前端应用  
✅ 希望简化前端配置  

### 🔧 工作原理

```
┌─────────────────────┐         ┌──────────────────┐
│   纯前端应用         │         │  Laravel Backend  │
│                     │         │                  │
│  请求:               │         │  1. 读取 Origin   │
│  GET /api/project   │────────▶│  2. 根据域名查找   │
│  /posts             │         │     对应的项目     │
│                     │         │  3. 自动获取 UUID  │
│  Headers:           │         │  4. 验证权限      │
│  Origin:            │         │  5. 返回数据      │
│  https://example.com│         │                  │
└─────────────────────┘         └──────────────────┘
```

### 📝 使用步骤

#### 1. 配置 API Allowed Domains

在后台管理界面添加允许调用本 API 的客户端域名：

```bash
项目设置 → API Settings → API Allowed Domains

添加以下域名：
- https://example.com
- https://www.example.com
- http://localhost:3000 (开发环境)
```

⚠️ **重要**：必须包含完整的协议（http:// 或 https://）

#### 2. 确保项目是公开的

```bash
项目设置 → API Settings → Public API
✅ 勾选 "Enable Public API Access"
```

#### 3. 发起 API 请求（无需 UUID 和 Token）

**React (Fetch)**：
```javascript
// ✅ 不需要传递 UUID
// ✅ 不需要传递 Token
// ✅ 浏览器自动添加 Origin 头

fetch('https://backend.com/api/project/posts')
    .then(res => res.json())
    .then(data => console.log(data));
```

**Vue 3 (Axios)**：
```javascript
import axios from 'axios';

// ✅ 简洁的 URL，没有 UUID
const response = await axios.get('https://backend.com/api/project/posts');

console.log(response.data);
```

**Next.js (SSR)**：
```javascript
export async function getServerSideProps() {
    const res = await fetch('https://backend.com/api/project/posts', {
        headers: {
            // SSR 需要手动设置 Origin
            'Origin': 'https://example.com'
        }
    });
    
    const posts = await res.json();
    
    return { props: { posts } };
}
```

### 📚 API 端点

所有端点都基于 `/api/project` 前缀。
其中`slug`是集合名称，包含比如：`pages`/`posts`/`categories`/`authors`/`tags`/`comments`/`globals`

| 方法 | 端点 | 说明 |
|------|------|------|
| GET | `/api/project` | 获取项目信息 |
| GET | `/api/project/{slug}` | 获取内容列表 |
| GET | `/api/project/{slug}/{id}` | 获取单条内容 |
| GET | `/api/project/media` | 获取媒体库 |
| GET | `/api/project/media/{id}` | 获取单个媒体 |

### 🔐 权限控制

| 条件 | 访问权限 |
|------|---------|
| ✅ 域名在 API Allowed Domains 白名单中 | 允许访问公开 API |
| ❌ 域名不在白名单中 | 拒绝访问（403） |
| ❌ 缺少 Origin 头 | 拒绝访问（403） |
| ✅ Public API 未启用 | 需要 Token（同方式 1） |

### ⚠️ 注意事项

1. **必须配置 API Allowed Domains**：否则无法识别项目
2. **Origin 头很重要**：浏览器会自动添加，但 SSR 需要手动设置
3. **仅支持公开 API**：写操作仍需 Token
4. **单项目限制**：一个域名只能对应一个项目

---

## 两种方式对比

### 📊 功能对比表

| 特性 | 方式 1：UUID + Token | 方式 2：域名自动解析 |
|------|---------------------|---------------------|
| **需要 UUID** | ✅ 是 | ❌ 否 |
| **需要 Token** | ✅ 公开 API 可选<br>❌ 私有 API 必需 | ❌ 不需要（公开 API） |
| **需要配置域名** | ⚠️ 推荐 | ✅ 必需 |
| **前端复杂度** | 中等 | 简单 |
| **安全性** | 高（Token 认证） | 中（域名白名单） |
| **适用框架** | 所有 | 所有 |
| **多项目支持** | ✅ 容易 | ❌ 困难 |
| **向后兼容** | ✅ 完全兼容 | ✅ 新增功能 |
| **CORS 验证** | ✅ 有 | ✅ 有 |

### 🎯 选择建议

**使用方式 1（UUID + Token）如果**：
- ✅ 你有多个前端项目共享同一个后端
- ✅ 你需要更高的安全性（Token 认证）
- ✅ 你是 Laravel 前端项目
- ✅ 你需要调用写操作（POST/PUT/DELETE）

**使用方式 2（域名自动解析）如果**：
- ✅ 你是纯前端项目（React/Vue/Angular）
- ✅ 你只有一个前端项目
- ✅ 你只想读取公开内容
- ✅ 你不想在前端代码中暴露 UUID

---

## 快速开始

### 🚀 5 分钟快速集成（方式 2）

#### 第 1 步：配置后端（2 分钟）

1. 登录后台管理
2. 进入项目设置 → API Settings
3. 添加 API Allowed Domains：
   ```
   https://your-frontend.com
   http://localhost:3000
   ```
4. 启用 Public API：✅

#### 第 2 步：前端调用（3 分钟）

**React 示例**：
```jsx
import { useState, useEffect } from 'react';

function App() {
    const [posts, setPosts] = useState([]);

    useEffect(() => {
        fetch('https://backend.com/api/project/posts')
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
    const res = await fetch('https://backend.com/api/project/posts');
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

#### 第 3 步：测试（1 分钟）

```bash
# 本地测试
curl -H "Origin: http://localhost:3000" \
     https://backend.com/api/project/posts

# 应该返回 JSON 数据
```

---

## 常见问题

### Q1: 两种方式可以同时使用吗？

**A**: ✅ 可以！两种方式互不影响，你可以同时使用。

```javascript
// 方式 1：带 UUID
fetch('https://backend.com/api/abc123/posts', {
    headers: { 'Authorization': 'Bearer token' }
});

// 方式 2：不带 UUID
fetch('https://backend.com/api/project/posts');
```

### Q2: 方式 2 需要 Token 吗？

**A**: 
- 读取公开内容：❌ 不需要
- 写入内容（POST/PUT/DELETE）：✅ 需要（同方式 1）

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

### Q6: 如何从方式 2 迁移到方式 1？

**A**: 只需修改 URL：
```javascript
// 方式 2
fetch('https://backend.com/api/project/posts');

// 方式 1
fetch('https://backend.com/api/abc123/posts', {
    headers: { 'Authorization': 'Bearer token' }
});
```

### Q7: 性能有差异吗？

**A**: 
- 方式 2 多了一次数据库查询（根据域名查找项目）
- 差异微乎其微（< 1ms）
- 可以通过缓存优化

### Q8: 哪个更安全？

**A**: 
- 方式 1：Token 认证，更安全
- 方式 2：域名白名单，适合公开内容
- 建议：敏感数据用方式 1，公开内容用方式 2

---

## 技术细节

### 中间件工作流程

#### VerifyApiAllowedDomain（方式 1）

```php
// app/Http/Middleware/VerifyApiAllowedDomain.php

1. 从 URL 提取 UUID
2. 根据 UUID 查找项目
3. 如果没有配置 api_allowed_domains → 跳过验证（向后兼容）
4. 如果有属于该项目的有效 Token → 可跳过 Origin 校验
5. 检查 Origin/Referer 是否在 API Allowed Domains 白名单中
6. 通过 → 继续处理请求
```

#### ResolveProjectByApiAllowedDomain（方式 2）

```php
// app/Http/Middleware/ResolveProjectByApiAllowedDomain.php

1. 从 Origin/Referer 提取域名
2. 根据域名查找项目（查询 api_allowed_domains 字段）
3. 将项目对象注入到 request 中
4. 控制器从 request 中获取项目
5. 复用原有的业务逻辑
```

### 数据库查询

**方式 1**：
```sql
SELECT * FROM projects WHERE uuid = 'abc123-def456' LIMIT 1;
-- 1 次查询，使用索引
```

**方式 2**：
```sql
SELECT * FROM projects 
WHERE JSON_CONTAINS(api_allowed_domains, '"https://example.com"') 
LIMIT 1;
-- 1 次查询，JSON 字段查询
```

### 缓存建议

对于方式 2，可以缓存域名到项目的映射：

```php
// 伪代码
$project = Cache::remember("project_by_domain:{$domain}", 3600, function () use ($domain) {
    return Project::whereJsonContains('api_allowed_domains', $domain)->first();
});
```

---

## 总结

### ✅ 核心要点

1. **两种方式并存**：互不影响，按需选择
2. **向后兼容**：原有功能完全保留
3. **简化前端**：方式 2 让前端更简洁
4. **安全第一**：根据需求选择合适的认证方式

### 🎯 最佳实践

- **Laravel 前端** → 使用方式 1（UUID + Token）
- **纯前端项目** → 使用方式 2（域名自动解析）
- **敏感数据** → 使用方式 1 + 关闭 Public API
- **公开内容** → 使用方式 2 + 配置域名白名单
- **混合场景** → 两种方式结合使用

### 📞 需要帮助？

如有问题，请查看：
- [完整集成指南](PURE_FRONTEND_INTEGRATION_GUIDE.md)
- [API 兼容性说明](API_COMPATIBILITY_FIX.md)

---

**祝你集成顺利！** 🎉
