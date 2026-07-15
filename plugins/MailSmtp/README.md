# MailSmtp 插件目录

```text
plugins/MailSmtp/
├── plugin.json
├── frontend/                # 前台前端（站点 / 主题侧）
├── backend/README.md        # 占位：后台前端开发态见下行
├── src/                     # PHP
├── resources/lang/          # 后端语言包
└── ...

# 后台前端（管理端）开发态：
apps/admin/web-antd/src/plugins/mail-smtp/
├── panel.ts
├── *.vue
└── locales/{zh-CN,en-US}/
```

管理端 `src/plugins/registry.ts` 扫描 `./*/panel.ts`。  
导出包：`plugin/`（本目录，不含占位 backend）+ `backend/`（来自 src/plugins/mail-smtp）。
