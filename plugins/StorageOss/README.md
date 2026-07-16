# StorageOss

阿里云 OSS 存储适配插件：启用后覆盖 `StorageInterface`（S3 兼容 API）。

## 使用

```bash
php artisan starship:plugin:install storage-oss
php artisan starship:plugin:enable storage-oss
```

管理端「插件管理」→ 配置 AccessKey / Bucket 等 →「测试」探测上传删除。

禁用后回落 System `LocalStorage`。与系统设置页 `storage.oss.*` 是两条配置线；本插件走插件配置覆盖 Contract。
