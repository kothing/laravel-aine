# 对比总览：本项目 vs 业界无头 CMS

对比对象：Directus、Strapi、Sanity、Contentful、Payload、Ghost（业界主流无头 CMS）

## 功能对比表

| 功能 | 业界主流 | 本项目 | 状态 |
|------|---------|--------|------|
| 内容建模（集合/字段） | ✅ | ✅ | 已有 |
| 关系/关联字段 | ✅ | ✅ | 已有 |
| 版本历史 | ✅ | ✅ | F1 已补 |
| 回收站 | ✅ | ✅ | 已有 |
| 定时发布 | ✅ | ✅ | F3 已补 |
| 导入/导出（JSON/CSV） | ✅ | ✅ | F4 已补 |
| 全文搜索 | ✅ | ✅（LIKE 搜索） | 已有（基础版） |
| 审计日志 | ✅ | ✅ | F5 已补 |
| Webhooks | ✅ | ✅ | 已有 |
| 多语言/本地化 | ✅ | ✅ | 已有 |
| 媒体库 | ✅ | ✅ | 已有 |
| API Token 管理 | ✅ | ✅ | 已有 |
| REST API | ✅ | ✅ | 已有 |
| 双因素认证（2FA） | ✅ | ✅ | F6-1 已补 |
| 集合 Schema 导出/导入 | ✅ | ✅ | F6-2 已补 |
| 站内通知/订阅 | ✅ | ✅ | F6-3 已补 |
| GraphQL API | 部分支持 | ❌ | 不实施（REST 已覆盖，成本高收益低） |
| 团队协作/实时编辑 | 部分支持 | ❌ | 不实施（成本高） |

## 缺失功能实施记录

| 功能 | 优先级 | 状态 | 说明 |
|------|--------|------|------|
| 双因素认证（2FA） | P0 | ✅ 已完成 | 登录两步验证 + 恢复码 + Profile 设置界面（RFC 6238） |
| 集合 Schema 导出/导入 | P2 | ✅ 已完成 | 集合结构 JSON 导出/导入，集合菜单入口 |
| 站内通知/订阅 | P1 | ✅ 已完成 | 内容发布/取消发布/回收/删除/恢复事件通知，Topbar 铃铛 |
| GraphQL API | P2 | ❌ 不实施 | 已有 REST API 覆盖，成本高收益低 |

## 已补全功能详情

### F6-1 双因素认证（2FA）
- `App\Aine\TwoFactor`：纯 PHP RFC 6238 TOTP 实现（无外部依赖），通过 RFC 6238 附录 B 向量测试
- 登录流程：密码验证后若启用 2FA → 跳转 `/two-factor-challenge` 验证 TOTP 或恢复码
- 管理接口：`user/2fa/enable|confirm|disable|recovery-codes`
- Profile 页面：QR 码 + secret + 验证码确认 + 恢复码 + 禁用（需密码）

### F6-2 集合 Schema 导出/导入
- `GET collections/export-schema/{project_id}/{collection_id}`：导出集合结构 JSON
- `POST collections/import-schema/{project_id}`：导入（同 slug 集合更新字段，否则新建）
- 前端入口：集合菜单"Export Schema" / "Import Schema"

### F6-3 站内通知
- `notifications` 表（Laravel 原生格式）
- 触发：内容 publish/unpublish/trash/restore/delete 事件（通知 super_admin 与项目 admin）
- `GET admin-api/notifications`（列表 + 未读数）、`POST admin-api/notifications/read`
- Topbar 铃铛：未读徽标 + 下拉列表 + 标记已读

## 测试覆盖

- F1 版本历史：`ContentRevisionTest`（6）
- F2 全文搜索：`ContentSearchTest`（9）
- F3 定时发布：`ScheduledPublishingTest`（6）
- F4 导入/导出：`ContentImportExportTest`（8）
- F5 审计日志：`AuditLogTest`（14）
- F6-1 2FA：`TwoFactorTest`（17，含 RFC 6238 向量）
- F6-2 Schema：`CollectionSchemaTest`（4）
- F6-3 通知：`NotificationTest`（6）

合计 103 个测试全部通过（278 断言）。
