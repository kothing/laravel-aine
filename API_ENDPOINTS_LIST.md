# API 接口列表

## 方式 1：UUID + Token

**基础路径**: `/api/{uuid}/...`  
**特点**: 需要 UUID，支持读写操作

---

### 📁 项目管理接口

其中`slug`是集合名称，包含比如：`pages`/`posts`/`categories`/`authors`/`tags`/`comments`/`globals`

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 1 | GET | `/api/{uuid}` | 获取项目详情 | `uuid`: 项目UUID | 公开或Token |

---

### 📝 内容管理接口

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 2 | GET | `/api/{uuid}/{slug}` | 获取内容列表 | `uuid`: 项目UUID<br>`slug`: 集合名称<br>`page`: 页码(可选)<br>`per_page`: 每页数量(可选) | 公开或Token |
| 3 | GET | `/api/{uuid}/{slug}/{id}` | 获取单条内容 | `uuid`: 项目UUID<br>`slug`: 集合名称<br>`id`: 内容ID | 公开或Token |
| 4 | POST | `/api/{uuid}/{slug}` | 创建内容 | `uuid`: 项目UUID<br>`slug`: 集合名称<br>Body: 内容数据 | ✅ Token |
| 5 | POST | `/api/{uuid}/{slug}/update/{id}` | 更新内容 | `uuid`: 项目UUID<br>`slug`: 集合名称<br>`id`: 内容ID<br>Body: 更新数据 | ✅ Token |
| 6 | DELETE | `/api/{uuid}/{slug}/{id}` | 删除内容 | `uuid`: 项目UUID<br>`slug`: 集合名称<br>`id`: 内容ID | ✅ Token |

---

### 🖼️ 媒体库接口

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 7 | GET | `/api/{uuid}/project-media` | 获取媒体列表 | `uuid`: 项目UUID | 公开或Token |
| 8 | GET | `/api/{uuid}/project-media/{id}` | 根据ID获取媒体 | `uuid`: 项目UUID<br>`id`: 媒体ID | 公开或Token |
| 9 | GET | `/api/{uuid}/project-media/name/{name}` | 根据名称获取媒体 | `uuid`: 项目UUID<br>`name`: 文件名 | 公开或Token |
| 10 | POST | `/api/{uuid}/project-media/upload` | 上传媒体文件 | `uuid`: 项目UUID<br>Form: `file` 文件 | ✅ Token |
| 11 | DELETE | `/api/{uuid}/project-media/{id}` | 删除媒体文件 | `uuid`: 项目UUID<br>`id`: 媒体ID | ✅ Token |

---

## 方式 2：域名自动解析

**基础路径**: `/api/project/...`  
**特点**: 无需 UUID，仅支持读取

---

### 📊 项目信息接口

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 1 | GET | `/api/project/` | 获取当前项目详情 | 无（自动从Origin解析） | ❌ 无需 |

---

### 📝 内容管理接口（只读） 
其中`slug`是集合名称，包含比如：`pages`/`posts`/`categories`/`authors`/`tags`/`comments`/`globals`  

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 2 | GET | `/api/project/{slug}` | 获取内容列表 | `slug`: 集合名称<br>`page`: 页码(可选)<br>`per_page`: 每页数量(可选) | ❌ 无需 |
| 3 | GET | `/api/project/{slug}/{id}` | 获取单条内容 | `slug`: 集合名称<br>`id`: 内容ID | ❌ 无需 |

---

### 🖼️ 媒体库接口（只读）

| # | 方法 | 接口路径 | 说明 | 参数 | 认证 |
|---|------|---------|------|------|------|
| 4 | GET | `/api/project/media` | 获取媒体列表 | 无 | ❌ 无需 |
| 5 | GET | `/api/project/media/{id}` | 根据ID获取媒体 | `id`: 媒体ID | ❌ 无需 |

---

## 📊 接口统计

| 类别 | 方式 1 (UUID) | 方式 2 (域名) |
|------|--------------|--------------|
| **项目接口** | 1 | 1 |
| **内容读取** | 2 | 2 |
| **内容写入** | 3 | 0 |
| **媒体读取** | 3 | 2 |
| **媒体写入** | 2 | 0 |
| **总计** | **11** | **5** |

---

## 🔑 参数说明

### 路径参数

- `{uuid}`: 项目的唯一标识符（36位UUID）
- `{slug}`: 内容集合的名称（如：posts, pages, products）
- `{id}`: 内容或媒体的数字ID
- `{name}`: 媒体文件的文件名

### 查询参数

- `page`: 页码，默认 1
- `per_page`: 每页数量，默认 15

### 请求体参数（POST）

**创建/更新内容**:
```json
{
    "title": "标题",
    "slug": "别名",
    "content": "内容",
    "status": "draft|published",
    "meta": {}
}
```

**上传媒体**:
- Form Data: `file` (二进制文件)

### 请求头

**需要认证的接口**:
```
Authorization: Bearer {your_access_token}
Content-Type: application/json
Origin: https://your-domain.com
```

**公开接口**:
```
Origin: https://your-domain.com
```

---

## ✅ 认证要求说明

| 认证类型 | 说明 |
|---------|------|
| **公开或Token** | 如果项目 `public_api=true`，无需Token；否则需要Token |
| **✅ Token** | 必须提供有效的 Bearer Token，且Token具有 `write` 权限 |
| **❌ 无需** | 完全公开，不需要任何认证 |

---

## 🌐 完整URL示例

### 方式 1 示例

```
https://backend.com/api/abc123-def456
https://backend.com/api/abc123-def456/posts
https://backend.com/api/abc123-def456/posts/1
https://backend.com/api/abc123-def456/project-media
https://backend.com/api/abc123-def456/project-media/1
```

### 方式 2 示例

```
https://backend.com/api/project/
https://backend.com/api/project/posts
https://backend.com/api/project/posts/1
https://backend.com/api/project/media
https://backend.com/api/project/media/1
```
